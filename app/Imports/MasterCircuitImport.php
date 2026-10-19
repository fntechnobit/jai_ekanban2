<?php

namespace App\Imports;

use App\Models\MasterCircuit;
use App\Models\MasterConveyor;
use App\Models\MasterAssy;
use App\Models\MasterCircuitAssy;
use App\Config\CircuitTemplateConfig;
use App\Enums\CircuitType;
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
    protected $warnings = [];

    /** Kolom EJ/JAM yang ada di file: [kolom DB => indeks kolom Excel] */
    protected $extensionColumns = [];

    /** Baris CUTTING_JAM yang berhasil diimport: [id => ['codes' => [cct_code, cct_code_2], 'row' => nomor baris]] */
    protected $jamRows = [];

    /** Nomor baris ber-Type CUTTING yang diubah ke CUTTING_EJ karena QRCode EJ terisi */
    protected $promotedEjRows = [];

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

            // Kolom EJ / JAM (opsional) langsung setelah BA. File lama tanpa kolom
            // ini tetap valid untuk CUTTING / CUTTING_TWIST.
            $this->extensionColumns = $this->detectExtensionColumns($headerRow);

            // Get assy columns (after the last template column: BA/MEMORI TWIST, or
            // the EJ/JAM columns when the file has them)
            $assyColumns = [];
            $assyStart = CircuitTemplateConfig::getHeaderCount() + count($this->extensionColumns);
            for ($col = $assyStart; $col < count($headerRow); $col++) {
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
                    $data['type'] = $this->resolveEjType($data, $row);
                    $this->validateTypeData($data, $row);

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

                    if ($circuit->type === CircuitType::CUTTING_JAM->value) {
                        $this->jamRows[$circuit->id] = [
                            'codes' => array_filter([$circuit->cct_code, $circuit->cct_code_2]),
                            'row' => $row,
                        ];
                    }

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

            $this->warnDoubleKanbanForJamCircuits();

            // Taruh di depan: layar hasil import hanya menampilkan 5 peringatan pertama.
            if ($this->promotedEjRows) {
                $rows = $this->promotedEjRows;
                $list = implode(', ', array_slice($rows, 0, 10)) . (count($rows) > 10 ? ', ...' : '');
                array_unshift($this->warnings, count($rows) . " baris ber-Type CUTTING dengan QRCode EJ terisi disimpan sebagai CUTTING_EJ (baris {$list}).");
            }

            DB::commit();

            return [
                'success' => true,
                'total_rows' => $this->totalRows,
                'success_count' => $this->successCount,
                'failed_count' => $this->failedCount,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Kolom EJ/JAM dikenali bila header tepat setelah BA cocok berurutan dengan
     * CircuitTemplateConfig::getExtensionHeaders(). Tidak ada sama sekali = file lama
     * (valid); ada sebagian = template salah, ditolak agar kolom assy tidak bergeser.
     *
     * @return array<string,int> [kolom DB => indeks kolom Excel]
     */
    protected function detectExtensionColumns(array $headerRow): array
    {
        $start = CircuitTemplateConfig::getHeaderCount();
        $expected = CircuitTemplateConfig::getExtensionHeaders();
        $firstHeader = reset($expected);

        if (strcasecmp(trim((string) ($headerRow[$start] ?? '')), $firstHeader) !== 0) {
            return [];
        }

        $columns = [];
        $index = $start;
        foreach ($expected as $dbColumn => $header) {
            $uploaded = trim((string) ($headerRow[$index] ?? ''));
            if (strcasecmp($uploaded, $header) !== 0) {
                $columnLetter = ImportHelper::numberToColumnLetter($index + 1);
                throw new \Exception("Invalid template! Column {$columnLetter}: Expected '{$header}', found '{$uploaded}'.\n\nPlease download and use the correct Circuit template.");
            }
            $columns[$dbColumn] = $index;
            $index++;
        }

        return $columns;
    }

    /**
     * QRCode EJ-A / EJ-B hanya tercetak di tiket CUTTING_EJ. Baris ber-Type CUTTING
     * yang mengisinya dianggap CUTTING_EJ - tanpa ini tiket keluar format CUTTING
     * biasa dan QR EJ hilang diam-diam. Type lain dibiarkan, cukup diperingatkan.
     */
    protected function resolveEjType(array $data, int $row): string
    {
        $type = $data['type'];
        if (empty($data['qrcode_ej_a']) && empty($data['qrcode_ej_b'])) {
            return $type;
        }

        if ($type === CircuitType::CUTTING->value) {
            $this->promotedEjRows[] = $row;
            return CircuitType::CUTTING_EJ->value;
        }

        if ($type !== CircuitType::CUTTING_EJ->value) {
            $this->warnings[] = "Row {$row}: QRCode EJ terisi tetapi Type {$type}, QR EJ tidak tercetak di tiket {$type}.";
        }

        return $type;
    }

    /**
     * Data wajib per type. Baris yang tidak lengkap digagalkan (masuk errors),
     * bukan disimpan setengah jadi lalu tercetak tanpa data.
     */
    protected function validateTypeData(array $data, int $row): void
    {
        $type = $data['type'];
        if (!in_array($type, [CircuitType::CUTTING_EJ->value, CircuitType::CUTTING_JAM->value], true)) {
            return;
        }

        if (empty($this->extensionColumns)) {
            throw new \Exception("Type {$type} membutuhkan kolom EJ/JAM (BB-BL) yang tidak ada di file ini. Download template terbaru.");
        }

        if ($type === CircuitType::CUTTING_JAM->value) {
            if (empty($data['cct_code']) || empty($data['cct_code_2'])) {
                throw new \Exception('CUTTING_JAM wajib mengisi CCT Code dan CCT Code 2 (1 baris = 1 pasang circuit).');
            }
            if (strcasecmp($data['cct_code'], $data['cct_code_2']) === 0) {
                throw new \Exception("CUTTING_JAM: CCT Code dan CCT Code 2 tidak boleh sama ({$data['cct_code']}).");
            }
            if (empty($data['shikake_code'])) {
                $this->warnings[] = "Row {$row}: CUTTING_JAM tanpa Shikake Code (Twist No): TWIST NO di tiket kosong dan barcode kanban memakai CCT Code.";
            }
            if (empty($data['qrcode_mesin'])) {
                $this->warnings[] = "Row {$row}: CUTTING_JAM dengan QRCode Mesin kosong, QR barcode mesin tidak tercetak.";
            }
        }

        // Satu sisi kosong itu normal (EJ hanya di salah satu ujung circuit).
        if ($type === CircuitType::CUTTING_EJ->value && empty($data['qrcode_ej_a']) && empty($data['qrcode_ej_b'])) {
            $this->warnings[] = "Row {$row}: CUTTING_EJ dengan QRCode EJ-A dan EJ-B kosong, tiket EJ tercetak tanpa QR EJ.";
        }
    }

    /**
     * JAM bukan proses lanjutan cutting: mesin JAM memotong sekaligus men-twist, jadi
     * circuit yang dipasangkan di JAM tidak boleh juga aktif sebagai baris lain
     * (CUTTING / CUTTING_TWIST / EJ) di conveyor yang sama - generate akan mengeluarkan
     * kanban untuk keduanya. Tidak dihapus otomatis, hanya diperingatkan.
     */
    protected function warnDoubleKanbanForJamCircuits(): void
    {
        if (empty($this->jamRows)) {
            return;
        }

        $codes = collect($this->jamRows)->pluck('codes')->flatten()->unique()->values();

        $others = MasterCircuit::where('conveyor_id', $this->conveyorId)
            ->where('type', '!=', CircuitType::CUTTING_JAM->value)
            ->whereIn('cct_code', $codes)
            ->get(['id', 'type', 'cct_code', 'cct_no', 'to_store']);

        foreach ($this->jamRows as $jam) {
            foreach ($others->whereIn('cct_code', $jam['codes']) as $other) {
                $this->warnings[] = "Row {$jam['row']}: CCT Code {$other->cct_code} juga masih aktif sebagai {$other->type}"
                    . " (CCT No {$other->cct_no}, store {$other->to_store}). Kanban akan keluar dobel - hapus baris tersebut bila circuit ini diproses di mesin JAM.";
            }
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
        // Column A (index 0) - Type: CUTTING, CUTTING_TWIST, CUTTING_EJ or CUTTING_JAM
        // (label tampilan seperti "CUTTING EJ" / "EJ" juga diterima). Kosong = CUTTING;
        // nilai tak dikenal menggagalkan baris, bukan diam-diam dicetak sebagai CUTTING.
        $rawType = ImportHelper::cleanValue($rowData[0] ?? null);
        $circuitType = $rawType === null ? CircuitType::CUTTING : CircuitType::tryFromInput($rawType);
        if ($circuitType === null) {
            throw new \Exception("Type '{$rawType}' tidak dikenal. Isi salah satu: " . implode(', ', CircuitType::values()) . '.');
        }
        $type = $circuitType->value;

        // Kolom EJ/JAM (BB-BL) hanya diisi bila ada di file, supaya import file
        // lama tidak mengosongkan data EJ/JAM yang sudah tersimpan.
        $extension = [];
        foreach ($this->extensionColumns as $dbColumn => $index) {
            $extension[$dbColumn] = ImportHelper::cleanValue($rowData[$index] ?? null);
        }

        // Map Excel columns to database fields based on Template_Cutting.xlsx structure
        return $extension + [
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
