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
            'Carline',        // A - 0
            'Conveyor',       // B - 1
            'CCT No.',        // C - 2
            'Family',         // D - 3
            'Qty.',           // E - 4
            'Machine',        // F - 5
            'Sequence',       // G - 6
            'Released Note',  // H - 7
            'Cust No.',       // I - 8
            'Barcode Mesin',  // J - 9
            'Address',        // K - 10
            'CCT Code',       // L - 11
            'Kind',           // M - 12
            'Size',           // N - 13
            'Col',            // O - 14
            'C/L',            // P - 15
            'Terminal 1',     // Q - 16
            'Note 1',         // R - 17
            'Gold 1',         // S - 18
            'Strip 1',        // T - 19
            'Acc. 1',         // U - 20
            'Acc. 1A',        // V - 21
            'Tube 1',         // W - 22
            'Mark 1',         // X - 23
            'Remark 1',       // Y - 24
            'Terminal 2',     // Z - 25
            'Note 2',         // AA - 26
            'Gold 2',         // AB - 27
            'Strip 2',        // AC - 28
            'Acc 2',          // AD - 29
            'Acc 2A',         // AE - 30
            'Tube 2',         // AF - 31
            'Mark 2',         // AG - 32
            'Remark 2',       // AH - 33
            'TA',             // AI - 34
            'TB',             // AJ - 35
            'T01',            // AK - 36
            'T02',            // AL - 37
            'T03',            // AM - 38
            'T04',            // AN - 39
            'T05',            // AO - 40
            'T06',            // AP - 41
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
