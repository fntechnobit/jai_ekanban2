<?php

namespace App\Imports;

use App\Models\MasterShikake;
use App\Models\MasterConveyor;
use App\Models\MasterAssy;
use App\Models\MasterShikakeAssy;
use App\Config\ShikakeTemplateConfig;
use App\Helpers\ImportHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

abstract class BaseShikakeImport
{
    protected $conveyorId;
    protected $process;
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $totalRows = 0;
    
    /**
     * Header name to column index mapping
     * @var array
     */
    protected $headerIndexMap = [];

    public function __construct($conveyorId, $process)
    {
        $this->conveyorId = $conveyorId;
        $this->process = $process;
    }

    /**
     * Get expected headers for this import type
     * @return array
     */
    abstract protected function getExpectedHeaders(): array;

    /**
     * Get the process type name for error messages
     * @return string
     */
    abstract protected function getProcessName(): string;

    /**
     * Get the column index where assy columns start (after process-specific columns)
     * @return int
     */
    abstract protected function getAssyStartColumn(): int;

    /**
     * Map process-specific data from row
     * @param array $rowData
     * @param int $shikakeId
     * @return array
     */
    abstract protected function mapProcessData(array $rowData, int $shikakeId): array;

    /**
     * Create process-specific record
     * @param array $processData
     * @return void
     */
    abstract protected function createProcessRecord(array $processData): void;

    /**
     * Update or create process-specific record based on master_shikake_id
     * @param int $shikakeId
     * @param array $processData
     * @return void
     */
    protected function updateOrCreateProcessRecord(int $shikakeId, array $processData): void
    {
        // Default: delete old and create new (subclasses can override for more specific logic)
        $this->deleteProcessRecord($shikakeId);
        $this->createProcessRecord($processData);
    }

    /**
     * Delete process-specific record by shikake ID
     * @param int $shikakeId
     * @return void
     */
    abstract protected function deleteProcessRecord(int $shikakeId): void;

    /**
     * Build header name to column index mapping
     * @param array $headerRow
     * @return void
     */
    protected function buildHeaderIndexMap(array $headerRow): void
    {
        $this->headerIndexMap = [];
        foreach ($headerRow as $index => $header) {
            $headerName = trim($header ?? '');
            if ($headerName !== '') {
                $this->headerIndexMap[$headerName] = $index;
            }
        }
    }

    /**
     * Get cell value by header name
     * @param array $rowData
     * @param string $headerName
     * @return mixed
     * @throws \Exception
     */
    protected function getValueByHeader(array $rowData, string $headerName)
    {
        if (!isset($this->headerIndexMap[$headerName])) {
            throw new \Exception("Unknown header: '{$headerName}'. Please ensure the template has the correct column headers.");
        }
        
        $index = $this->headerIndexMap[$headerName];
        return $rowData[$index] ?? null;
    }

    public function import($filePath, $startRow = 2)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            // Read header row
            $headerRow = $worksheet->rangeToArray("A1:{$highestColumn}1", null, true, false)[0];
            
            // Validate template headers
            $validationResult = $this->validateHeaders($headerRow);
            if (!$validationResult['valid']) {
                throw new \Exception($validationResult['message']);
            }
            
            // Build header index map for header-based value lookup
            $this->buildHeaderIndexMap($headerRow);
            
            // Calculate total rows to import
            $this->totalRows = $highestRow - $startRow + 1;
            
            // Check if exceeds 1000 rows limit
            if ($this->totalRows > 1000) {
                throw new \Exception("Data exceeds 1000 rows limit. You are trying to upload {$this->totalRows} rows. Please split your data into smaller batches.");
            }

            // Get conveyor data
            $conveyor = MasterConveyor::findOrFail($this->conveyorId);
            
            // Get assy columns (after process-specific columns)
            $assyStartCol = $this->getAssyStartColumn();
            $assyColumns = [];
            for ($col = $assyStartCol; $col < count($headerRow); $col++) {
                $assyName = ImportHelper::cleanValue($headerRow[$col]);
                if ($assyName) {
                    $assyColumns[$col] = $assyName;
                }
            }

            DB::beginTransaction();

            for ($row = $startRow; $row <= $highestRow; $row++) {
                try {
                    $rowData = $worksheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false)[0];
                    
                    // Skip empty rows
                    if ($this->isEmptyRow($rowData)) {
                        continue;
                    }

                    // Map and update or create MasterShikake
                    $shikakeData = $this->mapShikakeData($rowData, $conveyor);
                    $machine = $shikakeData['machine'] ?? null;
                    $sequence = $shikakeData['sequence'] ?? null;
                    
                    if ($machine && $sequence !== null) {
                        // Look up including soft-deleted rows so a re-import after a
                        // "remove by conveyor" restores the existing master in place
                        // instead of creating a duplicate. Locked schedules still
                        // reference the original master_shikake_id, so creating a new
                        // row would orphan them (e.g. qrcode_drawing not printing).
                        $shikake = MasterShikake::withTrashed()->firstOrNew([
                            'conveyor_id' => $this->conveyorId,
                            'process' => $this->process,
                            'machine' => $machine,
                            'sequence' => $sequence,
                        ]);
                        if ($shikake->trashed()) {
                            $shikake->restore();
                            $shikake->deleted_by = null;
                        }
                        $shikake->fill($shikakeData);
                        $shikake->save();
                    } else {
                        $shikake = MasterShikake::create($shikakeData);
                    }
                    
                    // Map and update or create process-specific record
                    $processData = $this->mapProcessData($rowData, $shikake->id);
                    $this->updateOrCreateProcessRecord($shikake->id, $processData);
                    
                    // Sync assy relationships (remove old, add new)
                    $shikake->assemblies()->detach();
                    $this->processAssyRelationships($shikake, $rowData, $assyColumns);
                    
                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->failedCount++;
                    $this->errors[] = "Row {$row}: " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'total_rows' => $this->totalRows,
                'success_count' => $this->successCount,
                'failed_count' => $this->failedCount,
                'errors' => $this->errors
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function isEmptyRow($rowData)
    {
        return empty(array_filter($rowData, function ($value) {
            return !is_null($value) && $value !== '';
        }));
    }

    protected function validateHeaders($headerRow)
    {
        $expectedHeaders = $this->getExpectedHeaders();
        $processName = $this->getProcessName();

        // Check if we have at least the required columns
        if (count($headerRow) < count($expectedHeaders)) {
            return [
                'valid' => false,
                'message' => "Invalid template! The uploaded file has fewer columns than expected. Please use the correct {$processName} template."
            ];
        }

        // Validate columns match expected headers
        $mismatches = [];
        for ($i = 0; $i < count($expectedHeaders); $i++) {
            $uploadedHeader = trim($headerRow[$i] ?? '');
            $expectedHeader = $expectedHeaders[$i];
            
            if (strcasecmp($uploadedHeader, $expectedHeader) !== 0) {
                $columnLetter = ImportHelper::numberToColumnLetter($i + 1);
                $mismatches[] = "Column {$columnLetter}: Expected '{$expectedHeader}', found '{$uploadedHeader}'";
            }
        }

        if (!empty($mismatches)) {
            $errorMessage = "Invalid template! Header mismatch detected:\n" . implode("\n", array_slice($mismatches, 0, 5));
            if (count($mismatches) > 5) {
                $errorMessage .= "\n... and " . (count($mismatches) - 5) . " more errors";
            }
            $errorMessage .= "\n\nPlease download and use the correct {$processName} template.";
            
            return [
                'valid' => false,
                'message' => $errorMessage
            ];
        }

        return ['valid' => true];
    }

    protected function mapShikakeData($rowData, $conveyor)
    {
        // Note: issue, barcode_kanban, released_date are generated dynamically in assy_schedule_shikake table
        // released_note remains in master_shikake as a static field
        return [
            'conveyor_id' => $this->conveyorId,
            'process' => $this->process,
            'carline' => ImportHelper::cleanValue($this->getValueByHeader($rowData, 'Carline')),
            'conveyor' => ImportHelper::cleanValue($this->getValueByHeader($rowData, 'Conveyor')),
            'machine' => ImportHelper::cleanValue($this->getValueByHeader($rowData, 'Machine')),
            'qty' => ImportHelper::cleanNumeric($this->getValueByHeader($rowData, 'QTY')),
            'family' => ImportHelper::cleanValue($this->getValueByHeader($rowData, 'Family')),
            'sequence' => ImportHelper::cleanNumeric($this->getValueByHeader($rowData, 'Sequence')),
            'released_note' => ImportHelper::cleanValue($this->getValueByHeader($rowData, 'Released Note')),
            'created_by' => Auth::id(),
        ];
    }

    protected function processAssyRelationships($shikake, $rowData, $assyColumns)
    {
        foreach ($assyColumns as $colIndex => $assyName) {
            $value = ImportHelper::cleanValue($rowData[$colIndex] ?? null);
            
            // Only process if value is "1"
            if ($value === '1' || $value === 1) {
                // Find or create MasterAssy
                $masterAssy = MasterAssy::firstOrCreate(
                    ['assy' => $assyName],
                    ['assy' => $assyName]
                );
                
                // Create MasterShikakeAssy relationship if not exists
                MasterShikakeAssy::firstOrCreate([
                    'master_shikake_id' => $shikake->id,
                    'master_assy_id' => $masterAssy->id,
                ]);
            }
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailedCount()
    {
        return $this->failedCount;
    }
}
