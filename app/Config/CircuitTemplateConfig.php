<?php

namespace App\Config;

/**
 * Centralized configuration for Circuit (Cutting) import/export templates.
 * This class defines headers used by both import classes and template generation commands.
 */
class CircuitTemplateConfig
{
    /**
     * Get all Circuit headers
     * Matches the expected columns in Template_Cutting.xlsx
     */
    public static function getHeaders(): array
    {
        return [
            'Type',              // A - 0
            'Carline',           // B - 1
            'Conveyor',          // C - 2
            'CCT No.',           // D - 3
            'Family',            // E - 4
            'Qty.',              // F - 5
            'Machine',           // G - 6
            'Machine Twist',     // H - 7
            'Sequence',          // I - 8
            'Sequence 2',        // J - 9
            'Released Note',     // K - 10
            'Cust No.',          // L - 11
            'Barcode Mesin',     // M - 12
            'Barcode Navigasi',  // N - 13
            'Barcode Process',   // O - 14
            'Barcode Shikake',   // P - 15
            'Barcode Twist',     // Q - 16
            'QRCode Drawing',    // R - 17
            'To Store',          // S - 18
            'Address',           // T - 19
            'CCT Code',          // U - 20
            'Shikake Code',      // V - 21
            'Kind',              // W - 22
            'Size',              // X - 23
            'Col',               // Y - 24
            'C/L',               // Z - 25
            'Terminal 1',        // AA - 26
            'Note 1',            // AB - 27
            'Gold 1',            // AC - 28
            'Strip 1',           // AD - 29
            'Acc. 1',            // AE - 30
            'Acc. 1A',           // AF - 31
            'Tube 1',            // AG - 32
            'Mark 1',            // AH - 33
            'Remark 1',          // AI - 34
            'Terminal 2',        // AJ - 35
            'Note 2',            // AK - 36
            'Gold 2',            // AL - 37
            'Strip 2',           // AM - 38
            'Acc 2',             // AN - 39
            'Acc 2A',            // AO - 40
            'Tube 2',            // AP - 41
            'Mark 2',            // AQ - 42
            'Remark 2',          // AR - 43
            'TA',                // AS - 44
            'TB',                // AT - 45
            'T01',               // AU - 46
            'T02',               // AV - 47
            'T03',               // AW - 48
            'T04',               // AX - 49
            'T05',               // AY - 50
            'T06',               // AZ - 51
            'Memory Twist',      // BA - 52
        ];
    }

    /**
     * Kolom tambahan setelah Memory Twist (BA), dikelompokkan sesuai urutan rilis.
     * Tiap kelompok opsional saat import tetapi harus lengkap dan berurutan, dan
     * kelompok berikutnya hanya dikenali bila kelompok sebelumnya ada. Jadi ketiga
     * versi template tetap bisa diimport: tanpa kolom tambahan (kolom assy mulai di BB),
     * dengan BB-BL saja (assy mulai di BM), atau lengkap BB-BX (assy mulai di BY).
     * Key = kolom DB, value = header Excel.
     *
     * @return array<string, array<string,string>>
     */
    public static function getExtensionHeaderGroups(): array
    {
        return [
            // CUTTING_EJ & CUTTING_JAM (BB-BL). QRCode Mesin juga dipakai Battery Cable.
            'ej_jam' => [
                'qrcode_ej_a' => 'QRCode EJ-A',          // BB - 53
                'qrcode_ej_b' => 'QRCode EJ-B',          // BC - 54
                'cct_code_2' => 'CCT Code 2',            // BD - 55
                'cct_no_2' => 'CCT No. 2',               // BE - 56
                'cust_no_2' => 'Cust No. 2',             // BF - 57
                'kind_2' => 'Kind 2',                    // BG - 58
                'size_2' => 'Size 2',                    // BH - 59
                'col_2' => 'Col 2',                      // BI - 60
                'cl_2' => 'C/L 2',                       // BJ - 61
                'barcode_mesin_2' => 'Barcode Mesin 2',  // BK - 62 (JAM: barcode mesin cadangan circuit 2)
                'qrcode_mesin' => 'QRCode Mesin',        // BL - 63 (JAM & Battery Cable: QR barcode mesin dari MTC)
            ],
            // CUTTING_MINICOAX, CUTTING_ANTENA_FEEDER, CUTTING_BATTERY_CABLE (BM-BX)
            'special' => [
                'acc_1b' => 'Acc. 1B',                   // BM - 64 (Minicoax: ACC3 sisi A)
                'acc_1c' => 'Acc. 1C',                   // BN - 65 (Minicoax: ACC4 sisi A)
                'acc_2b' => 'Acc 2B',                    // BO - 66 (Minicoax: ACC3 sisi B)
                'acc_2c' => 'Acc 2C',                    // BP - 67 (Minicoax: ACC4 sisi B)
                'barcode_process_2' => 'Barcode Process 2', // BQ - 68 (Minicoax: barcode proses B; A = Barcode Process kolom O)
                'part_code' => 'Kode',                   // BR - 69 (Antena Feeder: KODE)
                'part_no' => 'Part No.',                 // BS - 70 (Antena Feeder: PART NO.)
                'no_assy' => 'No Assy',                  // BT - 71 (Antena Feeder: NO ASSY)
                'qty_dc' => 'Qty DC',                    // BU - 72 (Battery Cable: QTY DC)
                't07' => 'T07',                          // BV - 73 (Battery Cable: TO 7)
                't08' => 'T08',                          // BW - 74 (Battery Cable: TO 8)
                't09' => 'T09',                          // BX - 75 (Battery Cable: TO 9)
            ],
        ];
    }

    /**
     * Semua kolom tambahan (BB-BX) dalam satu daftar. Key = kolom DB, value = header Excel.
     */
    public static function getExtensionHeaders(): array
    {
        return array_merge(...array_values(self::getExtensionHeaderGroups()));
    }

    /**
     * Get the count of header columns
     */
    public static function getHeaderCount(): int
    {
        return count(self::getHeaders());
    }

    /**
     * Get all template configurations for generation
     */
    public static function getAllTemplates(): array
    {
        return [
            'Template_Cutting.xlsx' => array_merge(self::getHeaders(), array_values(self::getExtensionHeaders())),
        ];
    }
}
