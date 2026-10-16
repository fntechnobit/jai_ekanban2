<?php

namespace App\Config;

/**
 * Centralized configuration for the Listing Manual import template.
 */
class ManualListingTemplateConfig
{
    /**
     * Header kolom, urut sesuai posisi di Excel (baris 1).
     * Tanggal, Conveyor, Assy Number, Qty dan Seq wajib; sisanya boleh kosong dan
     * diisi dari listing terakhir assy yang sama.
     */
    public static function getHeaders(): array
    {
        return [
            'Tanggal',     // A - 0  DD-MM-YYYY atau tanggal Excel
            'Conveyor',    // B - 1
            'Assy Number', // C - 2
            'Assy Code',   // D - 3
            'Qty',         // E - 4
            'Seq',         // F - 5
            'Carline',     // G - 6
            'SNP',         // H - 7
            'SNPA',        // I - 8
            'PLT',         // J - 9
            'Mode',        // K - 10 SEA / AIR
            'Keterangan',  // L - 11
        ];
    }

    /**
     * Lebar kolom template, sejajar dengan getHeaders().
     */
    public static function getColumnWidths(): array
    {
        return [14, 16, 20, 12, 8, 8, 14, 8, 8, 8, 8, 40];
    }
}
