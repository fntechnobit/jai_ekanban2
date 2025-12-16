<?php

namespace App\Imports;

use App\Models\MasterCircuit;
use App\Models\MasterConveyor;
use App\Models\MasterAssy;
use App\Models\MasterCircuitAssy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
                $assyName = $this->cleanValue($headerRow[$col]);
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
        // Expected headers for Circuit template (columns A-AR, 44 columns)
        $expectedHeaders = [
            'Conveyor',           // A - 0
            'CCT No.',            // B - 1
            'Family',             // C - 2
            'Qty.',               // D - 3
            'Issue',              // E - 4
            'Machine',            // F - 5
            'Sequence',           // G - 6
            'Barcode Kanban',     // H - 7
            'Released Date',      // I - 8
            'Released Note',      // J - 9
            'Cust No.',           // K - 10
            'Barcode Mesin',      // L - 11
            'Address',            // M - 12
            'CCT Code',           // N - 13
            'Kind',               // O - 14
            'Size',               // P - 15
            'Col',                // Q - 16
            'C/L',                // R - 17
            'Terminal 1',         // S - 18
            'Note 1',             // T - 19
            'Gold 1',             // U - 20
            'Strip 1',            // V - 21
            'Acc. 1',             // W - 22
            'Acc. 1A',            // X - 23
            'Tube 1',             // Y - 24
            'Mark 1',             // Z - 25
            'Remark 1',           // AA - 26
            'Terminal 2',         // AB - 27
            'Note 2',             // AC - 28
            'Gold 2',             // AD - 29
            'Strip 2',            // AE - 30
            'Acc 2',              // AF - 31
            'Acc 2A',             // AG - 32
            'Tube 2',             // AH - 33
            'Mark 2',             // AI - 34
            'Remark 2',           // AJ - 35
            'TA',                 // AK - 36
            'TB',                 // AL - 37
            'T01',                // AM - 38
            'T02',                // AN - 39
            'T03',                // AO - 40
            'T04',                // AP - 41
            'T05',                // AQ - 42
            'T06',                // AR - 43
        ];

        // Check if we have at least the required columns
        if (count($headerRow) < count($expectedHeaders)) {
            return [
                'valid' => false,
                'message' => 'Invalid template! The uploaded file has fewer columns than expected. Please use the correct Circuit template.'
            ];
        }

        // Validate first 44 columns match expected headers
        $mismatches = [];
        for ($i = 0; $i < count($expectedHeaders); $i++) {
            $uploadedHeader = trim($headerRow[$i] ?? '');
            $expectedHeader = $expectedHeaders[$i];
            
            if (strcasecmp($uploadedHeader, $expectedHeader) !== 0) {
                $columnLetter = $this->numberToColumnLetter($i + 1);
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
            $value = $this->cleanValue($rowData[$colIndex] ?? null);
            
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
            'conveyor' => $this->cleanValue($rowData[0] ?? null),        // Column A - Conveyor
            'cct_no' => $this->cleanValue($rowData[1] ?? null),          // Column B - CCT No.
            'family' => $this->cleanValue($rowData[2] ?? null),          // Column C - Family
            'qty' => $this->cleanNumeric($rowData[3] ?? null),           // Column D - Qty.
            'issue' => $this->cleanValue($rowData[4] ?? null),           // Column E - Issue
            'machine' => $this->cleanValue($rowData[5] ?? null),         // Column F - Machine
            'sequence' => $this->cleanValue($rowData[6] ?? null),        // Column G - Sequence
            'barcode_kanban' => $this->cleanValue($rowData[7] ?? null),  // Column H - Barcode Kanban
            'released_date' => $this->cleanDate($rowData[8] ?? null),    // Column I - Released Date
            'released_note' => $this->cleanValue($rowData[9] ?? null),   // Column J - Released Note
            'cust_no' => $this->cleanValue($rowData[10] ?? null),        // Column K - Cust No.
            'barcode_mesin' => $this->cleanValue($rowData[11] ?? null),  // Column L - Barcode Mesin
            'address' => $this->cleanValue($rowData[12] ?? null),        // Column M - Address
            'cct_code' => $this->cleanValue($rowData[13] ?? null),       // Column N - CCT Code
            'kind' => $this->cleanValue($rowData[14] ?? null),           // Column O - Kind
            'size' => $this->cleanValue($rowData[15] ?? null),           // Column P - Size
            'col' => $this->cleanValue($rowData[16] ?? null),            // Column Q - Col
            'cl' => $this->cleanValue($rowData[17] ?? null),             // Column R - C/L
            'terminal_1' => $this->cleanValue($rowData[18] ?? null),     // Column S - Terminal 1
            'note_1' => $this->cleanValue($rowData[19] ?? null),         // Column T - Note 1
            'gold_1' => $this->cleanValue($rowData[20] ?? null),         // Column U - Gold 1
            'strip_1' => $this->cleanValue($rowData[21] ?? null),        // Column V - Strip 1
            'acc_1' => $this->cleanValue($rowData[22] ?? null),          // Column W - Acc. 1
            'acc_1a' => $this->cleanValue($rowData[23] ?? null),         // Column X - Acc. 1A
            'tube_1' => $this->cleanValue($rowData[24] ?? null),         // Column Y - Tube 1
            'mark_1' => $this->cleanValue($rowData[25] ?? null),         // Column Z - Mark 1
            'remark_1' => $this->cleanValue($rowData[26] ?? null),       // Column AA - Remark 1
            'terminal_2' => $this->cleanValue($rowData[27] ?? null),     // Column AB - Terminal 2
            'note_2' => $this->cleanValue($rowData[28] ?? null),         // Column AC - Note 2
            'gold_2' => $this->cleanValue($rowData[29] ?? null),         // Column AD - Gold 2
            'strip_2' => $this->cleanValue($rowData[30] ?? null),        // Column AE - Strip 2
            'acc_2' => $this->cleanValue($rowData[31] ?? null),          // Column AF - Acc 2
            'acc_2a' => $this->cleanValue($rowData[32] ?? null),         // Column AG - Acc 2A
            'tube_2' => $this->cleanValue($rowData[33] ?? null),         // Column AH - Tube 2
            'mark_2' => $this->cleanValue($rowData[34] ?? null),         // Column AI - Mark 2
            'remark_2' => $this->cleanValue($rowData[35] ?? null),       // Column AJ - Remark 2
            'ta' => $this->cleanValue($rowData[36] ?? null),             // Column AK - TA
            'tb' => $this->cleanValue($rowData[37] ?? null),             // Column AL - TB
            't01' => $this->cleanValue($rowData[38] ?? null),            // Column AM - T01
            't02' => $this->cleanValue($rowData[39] ?? null),            // Column AN - T02
            't03' => $this->cleanValue($rowData[40] ?? null),            // Column AO - T03
            't04' => $this->cleanValue($rowData[41] ?? null),            // Column AP - T04
            't05' => $this->cleanValue($rowData[42] ?? null),            // Column AQ - T05
            't06' => $this->cleanValue($rowData[43] ?? null),            // Column AR - T06
            'created_by' => Auth::id(),
        ];
    }

    protected function cleanValue($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return trim((string) $value);
    }

    protected function cleanNumeric($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return is_numeric($value) ? (int) $value : null;
    }

    protected function cleanDate($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        try {
            // Handle Excel date serial numbers
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            
            // Handle string dates
            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return null;
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
