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

            // Get assy columns (after column AP/T03 which is index 41, so assy starts at 42)
            $assyColumns = [];
            for ($col = 49; $col < count($headerRow); $col++) {
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
        // Get expected headers from centralized config
        $expectedHeaders = CircuitTemplateConfig::getHeaders();

        // Check if we have at least the required columns
        if (count($headerRow) < count($expectedHeaders)) {
            return [
                'valid' => false,
                'message' => 'Invalid template! The uploaded file has fewer columns than expected. Please use the correct Circuit template.'
            ];
        }

        // Validate first 49 columns match expected headers
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
        // Read type from column A (index 0)
        $type = strtoupper(trim($rowData[0] ?? 'CUTTING'));

        // Validate type - default to CUTTING if invalid
        if (!in_array($type, [MasterCircuit::TYPE_CUTTING, MasterCircuit::TYPE_CUTTING_TWIST])) {
            $type = MasterCircuit::TYPE_CUTTING;
        }

        // Map Excel columns to database fields based on updated Template_Cutting.xlsx structure
        $data = [
            'conveyor_id' => $this->conveyorId,
            'type' => $type,                                                    // Column A - Type
            'carline' => ImportHelper::cleanValue($rowData[1] ?? null),         // Column B - Carline
            'conveyor' => ImportHelper::cleanValue($rowData[2] ?? null),        // Column C - Conveyor
            'cct_no' => ImportHelper::cleanValue($rowData[3] ?? null),          // Column D - CCT No.
            'family' => ImportHelper::cleanValue($rowData[4] ?? null),          // Column E - Family
            'qty' => ImportHelper::cleanNumeric($rowData[5] ?? null),           // Column F - Qty.
            'machine' => ImportHelper::cleanValue($rowData[6] ?? null),         // Column G - Machine
            'machine_twist' => null,                                            // Column H - Machine Twist (CUTTING_TWIST only)
            'sequence' => ImportHelper::cleanValue($rowData[8] ?? null),        // Column I - Sequence
            'sequence_2' => null,                                               // Column J - Sequence 2 (CUTTING_TWIST only)
            'released_note' => ImportHelper::cleanValue($rowData[10] ?? null),  // Column K - Released Note
            'cust_no' => ImportHelper::cleanValue($rowData[11] ?? null),        // Column L - Cust No.
            'barcode_mesin' => ImportHelper::cleanValue($rowData[12] ?? null),  // Column M - Barcode Mesin
            'barcode_navigasi' => null,                                         // Column N - Barcode Navigasi (CUTTING_TWIST only)
            'barcode_process' => null,                                          // Column O - Barcode Process (CUTTING_TWIST only)
            'barcode_shikake' => null,                                          // Column P - Barcode Shikake (CUTTING_TWIST only)
            'to_store' => ImportHelper::cleanValue($rowData[16] ?? null),       // Column Q - To Store
            'address' => ImportHelper::cleanValue($rowData[17] ?? null),        // Column R - Address
            'cct_code' => ImportHelper::cleanValue($rowData[18] ?? null),       // Column S - CCT Code
            'kind' => ImportHelper::cleanValue($rowData[19] ?? null),           // Column T - Kind
            'size' => ImportHelper::cleanValue($rowData[20] ?? null),           // Column U - Size
            'col' => ImportHelper::cleanValue($rowData[21] ?? null),            // Column V - Col
            'cl' => ImportHelper::cleanValue($rowData[22] ?? null),             // Column W - C/L
            'terminal_1' => ImportHelper::cleanValue($rowData[23] ?? null),     // Column X - Terminal 1
            'note_1' => ImportHelper::cleanValue($rowData[24] ?? null),         // Column Y - Note 1
            'gold_1' => ImportHelper::cleanValue($rowData[25] ?? null),         // Column Z - Gold 1
            'strip_1' => ImportHelper::cleanValue($rowData[26] ?? null),        // Column AA - Strip 1
            'acc_1a' => ImportHelper::cleanValue($rowData[27] ?? null),         // Column AB - Acc. 1A
            'acc_1b' => ImportHelper::cleanValue($rowData[28] ?? null),         // Column AC - Acc. 1B
            'tube_1' => ImportHelper::cleanValue($rowData[29] ?? null),         // Column AD - Tube 1
            'mark_1' => ImportHelper::cleanValue($rowData[30] ?? null),         // Column AE - Mark 1
            'terminal_2' => ImportHelper::cleanValue($rowData[31] ?? null),     // Column AF - Terminal 2
            'note_2' => ImportHelper::cleanValue($rowData[32] ?? null),         // Column AG - Note 2
            'gold_2' => ImportHelper::cleanValue($rowData[33] ?? null),         // Column AH - Gold 2
            'strip_2' => ImportHelper::cleanValue($rowData[34] ?? null),        // Column AI - Strip 2
            'acc_2a' => ImportHelper::cleanValue($rowData[35] ?? null),         // Column AJ - Acc 2A
            'acc_2b' => ImportHelper::cleanValue($rowData[36] ?? null),         // Column AK - Acc 2B
            'tube_2' => ImportHelper::cleanValue($rowData[37] ?? null),         // Column AL - Tube 2
            'mark_2' => ImportHelper::cleanValue($rowData[38] ?? null),         // Column AM - Mark 2
            't01' => ImportHelper::cleanValue($rowData[39] ?? null),            // Column AN - T01
            't02' => ImportHelper::cleanValue($rowData[40] ?? null),            // Column AO - T02
            't03' => ImportHelper::cleanValue($rowData[41] ?? null),            // Column AP - T03
            'memory_twist' => null,                                              // Column AQ - Memory Twist (CUTTING_TWIST only)
            'created_by' => Auth::id(),
        ];

        // Add CUTTING_TWIST specific fields when type matches
        if ($type === MasterCircuit::TYPE_CUTTING_TWIST) {
            $data['machine_twist'] = ImportHelper::cleanValue($rowData[7] ?? null);     // Column H - Machine Twist
            $data['sequence_2'] = ImportHelper::cleanNumeric($rowData[9] ?? null);      // Column J - Sequence 2
            $data['barcode_navigasi'] = ImportHelper::cleanValue($rowData[13] ?? null); // Column N - Barcode Navigasi
            $data['barcode_process'] = ImportHelper::cleanValue($rowData[14] ?? null);  // Column O - Barcode Process
            $data['barcode_shikake'] = ImportHelper::cleanValue($rowData[15] ?? null);  // Column P - Barcode Shikake
            $data['memory_twist'] = ImportHelper::cleanValue($rowData[42] ?? null);     // Column AQ - Memory Twist
        }

        return $data;
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
