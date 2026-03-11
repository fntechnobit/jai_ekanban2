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
            'Template_Cutting.xlsx' => self::getHeaders(),
        ];
    }
}
