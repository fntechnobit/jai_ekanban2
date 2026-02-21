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
            'Shikake Code',      // T - 19
            'Kind',              // U - 20
            'Size',              // V - 21
            'Col',               // W - 22
            'C/L',               // X - 23
            'Terminal 1',        // Y - 24
            'Note 1',            // Z - 25
            'Gold 1',            // AA - 26
            'Strip 1',           // AB - 27
            'Acc. 1',            // AC - 28
            'Acc. 1A',           // AD - 29
            'Tube 1',            // AE - 30
            'Mark 1',            // AF - 31
            'Remark 1',          // AG - 32
            'Terminal 2',        // AH - 33
            'Note 2',            // AI - 34
            'Gold 2',            // AJ - 35
            'Strip 2',           // AK - 36
            'Acc 2',             // AL - 37
            'Acc 2A',            // AM - 38
            'Tube 2',            // AN - 39
            'Mark 2',            // AO - 40
            'Remark 2',          // AP - 41
            'TA',                // AQ - 42
            'TB',                // AR - 43
            'T01',               // AS - 44
            'T02',               // AT - 45
            'T03',               // AU - 46
            'T04',               // AV - 47
            'T05',               // AW - 48
            'T06',               // AX - 49
            'Memory Twist',      // AY - 50
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
