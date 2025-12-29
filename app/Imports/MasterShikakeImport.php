<?php

namespace App\Imports;

use App\Models\MasterShikake;
use App\Models\MasterConveyor;
use App\Models\MasterAssy;
use App\Models\MasterShikakeAssy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MasterShikakeImport
{
    protected $conveyorId;
    protected $process;
    protected $errors = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $totalRows = 0;

    public function __construct($conveyorId, $process = null)
    {
        $this->conveyorId = $conveyorId;
        $this->process = $process;
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
            
            // Get assy columns (after column AP which is index 41)
            $assyColumns = [];
            for ($col = 42; $col < count($headerRow); $col++) {
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
                    
                    // Create shikake
                    $shikake = MasterShikake::create($data);
                    
                    // Process assy relationships
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
        // Expected headers for Shikake template (columns A-AP, 42 columns)
        $expectedHeaders = [
            'Conveyor',           // A - 0
            'Shikake No',         // B - 1
            'Family',             // C - 2
            'QTY',                // D - 3
            'Issue',              // E - 4
            'Machine',            // F - 5
            'Sequence',           // G - 6
            'Barcode Kanban',     // H - 7
            'Released Date',      // I - 8
            'Released Note',      // J - 9
            'Store',              // K - 10
            'Barcode Mesin',      // L - 11
            'Address',            // M - 12
            'CCT A',              // N - 13
            'Address A',          // O - 14
            'CCT B',              // P - 15
            'Address B',          // Q - 16
            'CCT C',              // R - 17
            'Address C',          // S - 18
            'CCT 4',              // T - 19
            'Address 4',          // U - 20
            'CCT 5',              // V - 21
            'Address 5',          // W - 22
            'CCT 6',              // X - 23
            'Address 6',          // Y - 24
            'CCT 7',              // Z - 25
            'Address 7',          // AA - 26
            'Barcode Proses',     // AB - 27
            'Barcode Navigasi',   // AC - 28
            'Dies',               // AD - 29
            'Jumlah Kombinasi',   // AE - 30
            'Blade',              // AF - 31
            'T01',                // AG - 32
            'T02',                // AH - 33
            'T03',                // AI - 34
            'T04',                // AJ - 35
            'T05',                // AK - 36
            'T06',                // AL - 37
            'T07',                // AM - 38
            'T08',                // AN - 39
            'T09',                // AO - 40
            'Joint',              // AP - 41
        ];

        // Check if we have at least the required columns
        if (count($headerRow) < count($expectedHeaders)) {
            return [
                'valid' => false,
                'message' => 'Invalid template! The uploaded file has fewer columns than expected. Please use the correct Shikake template.'
            ];
        }

        // Validate first 42 columns match expected headers
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
            $errorMessage .= "\n\nPlease download and use the correct Shikake template.";
            
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

    protected function processAssyRelationships($shikake, $rowData, $assyColumns)
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
                
                // Create MasterShikakeAssy relationship if not exists
                MasterShikakeAssy::firstOrCreate([
                    'master_shikake_id' => $shikake->id,
                    'master_assy_id' => $masterAssy->id,
                ]);
            }
        }
    }

    protected function mapRowToData($rowData, $conveyor)
    {
        // Map Excel columns to database fields based on Template_Shikake.xlsx structure
        return [
            'conveyor_id' => $this->conveyorId,
            'conveyor' => $this->cleanValue($rowData[0] ?? null),           // Column A
            'process' => $this->process,
            'shikake_no' => $this->cleanValue($rowData[1] ?? null),         // Column B
            'family' => $this->cleanValue($rowData[2] ?? null),             // Column C
            'qty' => $this->cleanNumeric($rowData[3] ?? null),              // Column D
            'issue' => $this->cleanValue($rowData[4] ?? null),              // Column E
            'machine' => $this->cleanValue($rowData[5] ?? null),            // Column F
            'sequence' => $this->cleanNumeric($rowData[6] ?? null),         // Column G
            'barcode_kanban' => $this->cleanValue($rowData[7] ?? null),     // Column H
            'released_date' => $this->cleanDate($rowData[8] ?? null),       // Column I
            'released_note' => $this->cleanValue($rowData[9] ?? null),      // Column J
            'store' => $this->cleanValue($rowData[10] ?? null),             // Column K
            'barcode_mesin' => $this->cleanValue($rowData[11] ?? null),     // Column L
            'address' => $this->cleanValue($rowData[12] ?? null),           // Column M
            'cct_a' => $this->cleanValue($rowData[13] ?? null),             // Column N
            'address_a' => $this->cleanValue($rowData[14] ?? null),         // Column O
            'cct_b' => $this->cleanValue($rowData[15] ?? null),             // Column P
            'address_b' => $this->cleanValue($rowData[16] ?? null),         // Column Q
            'cct_c' => $this->cleanValue($rowData[17] ?? null),             // Column R
            'address_c' => $this->cleanValue($rowData[18] ?? null),         // Column S
            'cct_4' => $this->cleanValue($rowData[19] ?? null),             // Column T
            'address_4' => $this->cleanValue($rowData[20] ?? null),         // Column U
            'cct_5' => $this->cleanValue($rowData[21] ?? null),             // Column V
            'address_5' => $this->cleanValue($rowData[22] ?? null),         // Column W
            'cct_6' => $this->cleanValue($rowData[23] ?? null),             // Column X
            'address_6' => $this->cleanValue($rowData[24] ?? null),         // Column Y
            'cct_7' => $this->cleanValue($rowData[25] ?? null),             // Column Z
            'address_7' => $this->cleanValue($rowData[26] ?? null),         // Column AA
            'barcode_proses' => $this->cleanValue($rowData[27] ?? null),    // Column AB
            'barcode_navigasi' => $this->cleanValue($rowData[28] ?? null),  // Column AC
            'dies' => $this->cleanValue($rowData[29] ?? null),              // Column AD
            'jumlah_kombinasi' => $this->cleanNumeric($rowData[30] ?? null), // Column AE
            'blade' => $this->cleanValue($rowData[31] ?? null),             // Column AF
            't01' => $this->cleanValue($rowData[32] ?? null),               // Column AG
            't02' => $this->cleanValue($rowData[33] ?? null),               // Column AH
            't03' => $this->cleanValue($rowData[34] ?? null),               // Column AI
            't04' => $this->cleanValue($rowData[35] ?? null),               // Column AJ
            't05' => $this->cleanValue($rowData[36] ?? null),               // Column AK
            't06' => $this->cleanValue($rowData[37] ?? null),               // Column AL
            't07' => $this->cleanValue($rowData[38] ?? null),               // Column AM
            't08' => $this->cleanValue($rowData[39] ?? null),               // Column AN
            't09' => $this->cleanValue($rowData[40] ?? null),               // Column AO
            'joint' => $this->cleanValue($rowData[41] ?? null),             // Column AP
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
