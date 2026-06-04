<?php

namespace App\Imports;

use App\Models\MasterCircuit;
use App\Models\MasterConveyor;
use App\Models\MasterAssy;
use App\Models\MasterCircuitAssy;
use App\Config\CircuitTemplateConfig;
use App\Helpers\ImportHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MasterCircuitImport
{
    protected $conveyorId;
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $totalRows = 0;

    public function __construct($conveyorId)
    {
        $this->conveyorId = $conveyorId;
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
            
            // Calculate total rows to import
            $this->totalRows = $highestRow - $startRow + 1;
            
            // Check if exceeds 1000 rows limit
            if ($this->totalRows > 1000) {
                throw new \Exception("Data exceeds 1000 rows limit. You are trying to upload {$this->totalRows} rows. Please split your data into smaller batches.");
            }

            // Get conveyor data
            $conveyor = MasterConveyor::findOrFail($this->conveyorId);
            
            // Get assy columns (after column BA/MEMORI TWIST which is index 52)
            $assyColumns = [];
            for ($col = 53; $col < count($headerRow); $col++) {
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

                    $data = $this->mapRowToData($rowData, $conveyor);

                    // Update or create circuit based on conveyor_id + cct_code + to_store.
                    // Within one conveyor the same cct_code may appear several times as
                    // long as to_store differs, so to_store is part of the unique key.
                    $cctCode = $data['cct_code'] ?? null;
                    if ($cctCode) {
                        // Look up including soft-deleted rows so a re-import after a
                        // "remove by conveyor" restores the existing record instead of
                        // colliding with the unique constraint.
                        $circuit = MasterCircuit::withTrashed()->firstOrNew([
                            'conveyor_id' => $this->conveyorId,
                            'cct_code' => $cctCode,
                            'to_store' => $data['to_store'] ?? null,
                        ]);
                        if ($circuit->trashed()) {
                            $circuit->restore();
                            $circuit->deleted_by = null;
                        }
                        $circuit->fill($data);
                        $circuit->save();
                    } else {
                        $circuit = MasterCircuit::create($data);
                    }
                    
                    // Sync assy relationships (remove old, add new)
                    $circuit->assemblies()->detach();
                    $this->processAssyRelationships($circuit, $rowData, $assyColumns);
                    
                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->failedCount++;
                    $errorMessage = "Row {$row}: " . $e->getMessage();
                    if ($e->getPrevious()) {
                        $errorMessage .= " | " . $e->getPrevious()->getMessage();
                    }
                    $this->errors[] = $errorMessage;
                    \Log::error("Circuit Import Error on Row {$row}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
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
        // Get expected headers from centralized config
        $expectedHeaders = CircuitTemplateConfig::getHeaders();

        // Check if we have at least the required columns
        if (count($headerRow) < count($expectedHeaders)) {
            return [
                'valid' => false,
                'message' => 'Invalid template! The uploaded file has fewer columns than expected. Please use the correct Circuit template.'
            ];
        }

        // Validate first columns match expected headers
        $mismatches = [];
        for ($i = 0; $i < count($expectedHeaders); $i++) {
            $uploadedHeader = trim($headerRow[$i] ?? '');
            $expectedHeader = $expectedHeaders[$i];
            
            // Special handling for Memory Twist column - accept both variations
            if ($i === 52) { // Column BA (Memory Twist)
                $isValid = (strcasecmp($uploadedHeader, 'Memory Twist') === 0) || 
                           (strcasecmp($uploadedHeader, 'MEMORI TWIST') === 0);
                if (!$isValid) {
                    $columnLetter = ImportHelper::numberToColumnLetter($i + 1);
                    $mismatches[] = "Column {$columnLetter}: Expected 'Memory Twist' or 'MEMORI TWIST', found '{$uploadedHeader}'";
                }
            } else {
                if (strcasecmp($uploadedHeader, $expectedHeader) !== 0) {
                    $columnLetter = ImportHelper::numberToColumnLetter($i + 1);
                    $mismatches[] = "Column {$columnLetter}: Expected '{$expectedHeader}', found '{$uploadedHeader}'";
                }
            }
        }

        if (!empty($mismatches)) {
            $errorMessage = "Invalid template! Header mismatch detected:\n" . implode("\n", array_slice($mismatches, 0, 5));
            if (count($mismatches) > 5) {
                $errorMessage .= "\n... and " . (count($mismatches) - 5) . " more errors";
            }
            $errorMessage .= "\n\nPlease download and use the correct Circuit template.";
            
            return [
                'valid' => false,
                'message' => $errorMessage
            ];
        }

        return ['valid' => true];
    }

    protected function numberToColumnLetter($number)
    {
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)) . $letter;
            $number = intdiv($number, 26);
        }
        return $letter;
    }

    protected function processAssyRelationships($circuit, $rowData, $assyColumns)
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
                
                // Create MasterCircuitAssy relationship if not exists
                MasterCircuitAssy::firstOrCreate([
                    'master_circuit_id' => $circuit->id,
                    'master_assy_id' => $masterAssy->id,
                ]);
            }
        }
    }

    protected function mapRowToData($rowData, $conveyor)
    {
        // Column A (index 0) - Type: CUTTING or CUTTING_TWIST
        $typeRaw = ImportHelper::cleanValue($rowData[0] ?? '');
        $type = strtoupper($typeRaw);
        
        // Validate: Only CUTTING or CUTTING_TWIST allowed, default to CUTTING
        if (!in_array($type, ['CUTTING', 'CUTTING_TWIST'])) {
            $type = 'CUTTING';
        }
        
        // Map Excel columns to database fields based on Template_Cutting.xlsx structure
        return [
            'conveyor_id' => $this->conveyorId,
            'type' => $type,                                                    // Column A - Type
            'carline' => ImportHelper::cleanValue($rowData[1] ?? null),         // Column B - Carline
            'conveyor' => ImportHelper::cleanValue($rowData[2] ?? null),        // Column C - Conveyor
            'cct_no' => ImportHelper::cleanValue($rowData[3] ?? null),          // Column D - CCT No.
            'family' => ImportHelper::cleanValue($rowData[4] ?? null),          // Column E - Family
            'qty' => ImportHelper::cleanNumeric($rowData[5] ?? null),           // Column F - Qty.
            'machine' => ImportHelper::cleanValue($rowData[6] ?? null),         // Column G - Machine
            'machine_twist' => ImportHelper::cleanValue($rowData[7] ?? null),   // Column H - Machine Twist
            'sequence' => ImportHelper::cleanValue($rowData[8] ?? null),        // Column I - Sequence
            'sequence_2' => ImportHelper::cleanNumeric($rowData[9] ?? null),    // Column J - Sequence 2
            'released_note' => ImportHelper::cleanValue($rowData[10] ?? null),  // Column K - Released Note
            'cust_no' => ImportHelper::cleanValue($rowData[11] ?? null),        // Column L - Cust No.
            'barcode_mesin' => ImportHelper::cleanValue($rowData[12] ?? null),  // Column M - Barcode Mesin
            'barcode_navigasi' => ImportHelper::cleanValue($rowData[13] ?? null), // Column N - Barcode Navigasi
            'barcode_process' => ImportHelper::cleanValue($rowData[14] ?? null),  // Column O - Barcode Process
            'barcode_shikake' => ImportHelper::cleanValue($rowData[15] ?? null),  // Column P - Barcode Shikake
            'barcode_twist' => ImportHelper::cleanValue($rowData[16] ?? null),    // Column Q - Barcode Twist
            'qrcode_drawing' => ImportHelper::cleanValue($rowData[17] ?? null),   // Column R - QRCode Drawing
            'to_store' => ImportHelper::cleanValue($rowData[18] ?? null),       // Column S - To Store
            'address' => ImportHelper::cleanValue($rowData[19] ?? null),        // Column T - Address
            'cct_code' => ImportHelper::cleanValue($rowData[20] ?? null),       // Column U - CCT Code
            'shikake_code' => ImportHelper::cleanValue($rowData[21] ?? null),   // Column V - Shikake Code
            'kind' => ImportHelper::cleanValue($rowData[22] ?? null),           // Column W - Kind
            'size' => ImportHelper::cleanValue($rowData[23] ?? null),           // Column X - Size
            'col' => ImportHelper::cleanValue($rowData[24] ?? null),            // Column Y - Col
            'cl' => ImportHelper::cleanValue($rowData[25] ?? null),             // Column Z - C/L
            'terminal_1' => ImportHelper::cleanValue($rowData[26] ?? null),     // Column AA - Terminal 1
            'note_1' => ImportHelper::cleanValue($rowData[27] ?? null),         // Column AB - Note 1
            'gold_1' => ImportHelper::cleanValue($rowData[28] ?? null),         // Column AC - Gold 1
            'strip_1' => ImportHelper::cleanValue($rowData[29] ?? null),        // Column AD - Strip 1
            'acc_1' => ImportHelper::cleanValue($rowData[30] ?? null),          // Column AE - Acc. 1
            'acc_1a' => ImportHelper::cleanValue($rowData[31] ?? null),         // Column AF - Acc. 1A
            'tube_1' => ImportHelper::cleanValue($rowData[32] ?? null),         // Column AG - Tube 1
            'mark_1' => ImportHelper::cleanValue($rowData[33] ?? null),         // Column AH - Mark 1
            // Column AI (index 34) - Remark 1: not in DB schema, skipped
            'terminal_2' => ImportHelper::cleanValue($rowData[35] ?? null),     // Column AJ - Terminal 2
            'note_2' => ImportHelper::cleanValue($rowData[36] ?? null),         // Column AK - Note 2
            'gold_2' => ImportHelper::cleanValue($rowData[37] ?? null),         // Column AL - Gold 2
            'strip_2' => ImportHelper::cleanValue($rowData[38] ?? null),        // Column AM - Strip 2
            'acc_2' => ImportHelper::cleanValue($rowData[39] ?? null),          // Column AN - Acc 2
            'acc_2a' => ImportHelper::cleanValue($rowData[40] ?? null),         // Column AO - Acc 2A
            'tube_2' => ImportHelper::cleanValue($rowData[41] ?? null),         // Column AP - Tube 2
            'mark_2' => ImportHelper::cleanValue($rowData[42] ?? null),         // Column AQ - Mark 2
            // Column AR (index 43) - Remark 2: not in DB schema, skipped
            'ta' => ImportHelper::cleanValue($rowData[44] ?? null),             // Column AS - TA
            'tb' => ImportHelper::cleanValue($rowData[45] ?? null),             // Column AT - TB
            't01' => ImportHelper::cleanValue($rowData[46] ?? null),            // Column AU - T01
            't02' => ImportHelper::cleanValue($rowData[47] ?? null),            // Column AV - T02
            't03' => ImportHelper::cleanValue($rowData[48] ?? null),            // Column AW - T03
            // Column AX (index 49) - T04: not in DB schema, skipped
            // Column AY (index 50) - T05: not in DB schema, skipped
            // Column AZ (index 51) - T06: not in DB schema, skipped
            'memory_twist' => ImportHelper::cleanValue($rowData[52] ?? null),   // Column BA - Memori Twist
            'created_by' => Auth::id(),
        ];
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
