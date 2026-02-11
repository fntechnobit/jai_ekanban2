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
            'To Store',          // Q - 16
            'Address',           // R - 17
            'CCT Code',          // S - 18
            'Kind',              // T - 19
            'Size',              // U - 20
            'Col',               // V - 21
            'C/L',               // W - 22
            'Terminal 1',        // X - 23
            'Note 1',            // Y - 24
            'Gold 1',            // Z - 25
            'Strip 1',           // AA - 26
            'Acc. 1',            // AB - 27
            'Acc. 1A',           // AC - 28
            'Tube 1',            // AD - 29
            'Mark 1',            // AE - 30
            'Remark 1',          // AF - 31
            'Terminal 2',        // AG - 32
            'Note 2',            // AH - 33
            'Gold 2',            // AI - 34
            'Strip 2',           // AJ - 35
            'Acc 2',             // AK - 36
            'Acc 2A',            // AL - 37
            'Tube 2',            // AM - 38
            'Mark 2',            // AN - 39
            'Remark 2',          // AO - 40
            'TA',                // AP - 41
            'TB',                // AQ - 42
            'T01',               // AR - 43
            'T02',               // AS - 44
            'T03',               // AT - 45
            'T04',               // AU - 46
            'T05',               // AV - 47
            'T06',               // AW - 48
            'Memory Twist',      // AX - 49
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
