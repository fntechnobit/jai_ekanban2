<?php

namespace App\Imports;

use App\Enums\MachineType;
use App\Models\MasterArea;
use App\Models\MasterMachine;
use App\Config\MachineTemplateConfig;
use App\Helpers\ImportHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MasterMachineImport
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $totalRows = 0;

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

            // Calculate total rows to import
            $this->totalRows = $highestRow - $startRow + 1;

            // Check if exceeds 1000 rows limit
            if ($this->totalRows > 1000) {
                throw new \Exception("Data exceeds 1000 rows limit. You are trying to upload {$this->totalRows} rows. Please split your data into smaller batches.");
            }

            // Area name -> id, for resolving the Area column
            $areaIdsByName = MasterArea::pluck('id', 'area');
            $validTypes = MachineType::toArray();

            DB::beginTransaction();

            for ($row = $startRow; $row <= $highestRow; $row++) {
                try {
                    $rowData = $worksheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false)[0];

                    // Skip empty rows
                    if ($this->isEmptyRow($rowData)) {
                        continue;
                    }

                    $machineName = ImportHelper::cleanValue($rowData[0] ?? null);
                    $type = ImportHelper::cleanValue($rowData[1] ?? null);
                    $areaName = ImportHelper::cleanValue($rowData[2] ?? null);

                    if (!$machineName) {
                        throw new \Exception('Machine is required.');
                    }

                    if (!$type || !in_array($type, $validTypes, true)) {
                        throw new \Exception("Type '{$type}' is invalid. Must be one of: " . implode(', ', $validTypes) . '.');
                    }

                    if (!$areaName || !$areaIdsByName->has($areaName)) {
                        throw new \Exception("Area '{$areaName}' was not found in Master Area.");
                    }

                    $data = [
                        'machine' => $machineName,
                        'type' => $type,
                        'master_area_id' => $areaIdsByName->get($areaName),
                    ];

                    // Update or create by machine name. Conveyors are not part of
                    // the template (filled manually in the app) so they are left
                    // untouched on both create and update.
                    $machine = MasterMachine::withTrashed()->firstOrNew(['machine' => $machineName]);
                    if ($machine->trashed()) {
                        $machine->restore();
                        $machine->deleted_by = null;
                    }
                    $data['updated_by'] = Auth::id();
                    if (!$machine->exists) {
                        $data['created_by'] = Auth::id();
                    }
                    $machine->fill($data);
                    $machine->save();

                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->failedCount++;
                    $this->errors[] = "Row {$row}: " . $e->getMessage();
                    \Log::error("Master Machine Import Error on Row {$row}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'total_rows' => $this->totalRows,
                'success_count' => $this->successCount,
                'failed_count' => $this->failedCount,
                'errors' => $this->errors,
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
        $expectedHeaders = MachineTemplateConfig::getHeaders();

        if (count($headerRow) < count($expectedHeaders)) {
            return [
                'valid' => false,
                'message' => 'Invalid template! The uploaded file has fewer columns than expected. Please use the correct Machine template.',
            ];
        }

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
                $errorMessage .= "\n... and " . (count($mismatches) - 5) . ' more errors';
            }
            $errorMessage .= "\n\nPlease download and use the correct Machine template.";

            return [
                'valid' => false,
                'message' => $errorMessage,
            ];
        }

        return ['valid' => true];
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
