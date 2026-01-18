<?php

namespace App\Imports;

use App\Models\MasterCircuit;
use App\Models\MasterConveyor;
use App\Models\MasterAssy;
use App\Models\MasterCircuitAssy;
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
            
            // Get assy columns (after column AR/T06 which is index 43)
            $assyColumns = [];
            for ($col = 44; $col < count($headerRow); $col++) {
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
                    
                    // Create circuit
                    $circuit = MasterCircuit::create($data);
                    
                    // Process assy relationships
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
        // Expected headers for Circuit template (columns A-AO, 41 columns)
        $expectedHeaders = [
            'Carline',            // A - 0
            'Conveyor',           // B - 1
            'CCT No.',            // C - 2
            'Family',             // D - 3
            'Qty.',               // E - 4
            'Machine',            // F - 5
            'Sequence',           // G - 6
            'Cust No.',           // H - 7
            'Barcode Mesin',      // I - 8
            'Address',            // J - 9
            'CCT Code',           // K - 10
            'Kind',               // L - 11
            'Size',               // M - 12
            'Col',                // N - 13
            'C/L',                // O - 14
            'Terminal 1',         // P - 15
            'Note 1',             // Q - 16
            'Gold 1',             // R - 17
            'Strip 1',            // S - 18
            'Acc. 1',             // T - 19
            'Acc. 1A',            // U - 20
            'Tube 1',             // V - 21
            'Mark 1',             // W - 22
            'Remark 1',           // X - 23
            'Terminal 2',         // Y - 24
            'Note 2',             // Z - 25
            'Gold 2',             // AA - 26
            'Strip 2',            // AB - 27
            'Acc 2',              // AC - 28
            'Acc 2A',             // AD - 29
            'Tube 2',             // AE - 30
            'Mark 2',             // AF - 31
            'Remark 2',           // AG - 32
            'TA',                 // AH - 33
            'TB',                 // AI - 34
            'T01',                // AJ - 35
            'T02',                // AK - 36
            'T03',                // AL - 37
            'T04',                // AM - 38
            'T05',                // AN - 39
            'T06',                // AO - 40
        ];

        // Check if we have at least the required columns
        if (count($headerRow) < count($expectedHeaders)) {
            return [
                'valid' => false,
                'message' => 'Invalid template! The uploaded file has fewer columns than expected. Please use the correct Circuit template.'
            ];
        }

        // Validate first 41 columns match expected headers
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
        // Map Excel columns to database fields based on Template_Cutting.xlsx structure
        return [
            'conveyor_id' => $this->conveyorId,
            'carline' => ImportHelper::cleanValue($rowData[0] ?? null),         // Column A - Carline
            'conveyor' => ImportHelper::cleanValue($rowData[1] ?? null),        // Column B - Conveyor
            'cct_no' => ImportHelper::cleanValue($rowData[2] ?? null),          // Column C - CCT No.
            'family' => ImportHelper::cleanValue($rowData[3] ?? null),          // Column D - Family
            'qty' => ImportHelper::cleanNumeric($rowData[4] ?? null),           // Column E - Qty.
            'machine' => ImportHelper::cleanValue($rowData[5] ?? null),         // Column F - Machine
            'sequence' => ImportHelper::cleanValue($rowData[6] ?? null),        // Column G - Sequence
            'cust_no' => ImportHelper::cleanValue($rowData[7] ?? null),         // Column H - Cust No.
            'barcode_mesin' => ImportHelper::cleanValue($rowData[8] ?? null),   // Column I - Barcode Mesin
            'address' => ImportHelper::cleanValue($rowData[9] ?? null),         // Column J - Address
            'cct_code' => ImportHelper::cleanValue($rowData[10] ?? null),       // Column K - CCT Code
            'kind' => ImportHelper::cleanValue($rowData[11] ?? null),           // Column L - Kind
            'size' => ImportHelper::cleanValue($rowData[12] ?? null),           // Column M - Size
            'col' => ImportHelper::cleanValue($rowData[13] ?? null),            // Column N - Col
            'cl' => ImportHelper::cleanValue($rowData[14] ?? null),             // Column O - C/L
            'terminal_1' => ImportHelper::cleanValue($rowData[15] ?? null),     // Column P - Terminal 1
            'note_1' => ImportHelper::cleanValue($rowData[16] ?? null),         // Column Q - Note 1
            'gold_1' => ImportHelper::cleanValue($rowData[17] ?? null),         // Column R - Gold 1
            'strip_1' => ImportHelper::cleanValue($rowData[18] ?? null),        // Column S - Strip 1
            'acc_1' => ImportHelper::cleanValue($rowData[19] ?? null),          // Column T - Acc. 1
            'acc_1a' => ImportHelper::cleanValue($rowData[20] ?? null),         // Column U - Acc. 1A
            'tube_1' => ImportHelper::cleanValue($rowData[21] ?? null),         // Column V - Tube 1
            'mark_1' => ImportHelper::cleanValue($rowData[22] ?? null),         // Column W - Mark 1
            'remark_1' => ImportHelper::cleanValue($rowData[23] ?? null),       // Column X - Remark 1
            'terminal_2' => ImportHelper::cleanValue($rowData[24] ?? null),     // Column Y - Terminal 2
            'note_2' => ImportHelper::cleanValue($rowData[25] ?? null),         // Column Z - Note 2
            'gold_2' => ImportHelper::cleanValue($rowData[26] ?? null),         // Column AA - Gold 2
            'strip_2' => ImportHelper::cleanValue($rowData[27] ?? null),        // Column AB - Strip 2
            'acc_2' => ImportHelper::cleanValue($rowData[28] ?? null),          // Column AC - Acc 2
            'acc_2a' => ImportHelper::cleanValue($rowData[29] ?? null),         // Column AD - Acc 2A
            'tube_2' => ImportHelper::cleanValue($rowData[30] ?? null),         // Column AE - Tube 2
            'mark_2' => ImportHelper::cleanValue($rowData[31] ?? null),         // Column AF - Mark 2
            'remark_2' => ImportHelper::cleanValue($rowData[32] ?? null),       // Column AG - Remark 2
            'ta' => ImportHelper::cleanValue($rowData[33] ?? null),             // Column AH - TA
            'tb' => ImportHelper::cleanValue($rowData[34] ?? null),             // Column AI - TB
            't01' => ImportHelper::cleanValue($rowData[35] ?? null),            // Column AJ - T01
            't02' => ImportHelper::cleanValue($rowData[36] ?? null),            // Column AK - T02
            't03' => ImportHelper::cleanValue($rowData[37] ?? null),            // Column AL - T03
            't04' => ImportHelper::cleanValue($rowData[38] ?? null),            // Column AM - T04
            't05' => ImportHelper::cleanValue($rowData[39] ?? null),            // Column AN - T05
            't06' => ImportHelper::cleanValue($rowData[40] ?? null),            // Column AO - T06
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
