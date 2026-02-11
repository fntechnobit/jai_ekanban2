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
     * Supports both CUTTING and CUTTING_TWIST types
     */
    public static function getHeaders(): array
    {
        return [
            'Type',           // A - 0 (NEW - "CUTTING" or "CUTTING_TWIST")
            'Carline',        // B - 1
            'Conveyor',       // C - 2
            'CCT No.',        // D - 3
            'Family',         // E - 4
            'Qty.',           // F - 5
            'Machine',        // G - 6
            'Machine Twist',  // H - 7 (NEW - for CUTTING_TWIST)
            'Sequence',       // I - 8
            'Sequence 2',     // J - 9 (NEW - for CUTTING_TWIST)
            'Released Note',  // K - 10
            'Cust No.',       // L - 11
            'Barcode Mesin',  // M - 12
            'Barcode Navigasi', // N - 13 (NEW - for CUTTING_TWIST)
            'Barcode Process',  // O - 14 (NEW - for CUTTING_TWIST)
            'Barcode Shikake',  // P - 15 (NEW - for CUTTING_TWIST)
            'To Store',       // Q - 16
            'Address',        // R - 17
            'CCT Code',       // S - 18
            'Kind',           // T - 19
            'Size',           // U - 20
            'Col',            // V - 21
            'C/L',            // W - 22
            'Terminal 1',     // X - 23
            'Note 1',         // Y - 24
            'Gold 1',         // Z - 25
            'Strip 1',        // AA - 26
            'Acc. 1A',        // AB - 27
            'Acc. 1B',        // AC - 28
            'Tube 1',         // AD - 29
            'Mark 1',         // AE - 30
            'Terminal 2',     // AF - 31
            'Note 2',         // AG - 32
            'Gold 2',         // AH - 33
            'Strip 2',        // AI - 34
            'Acc 2A',         // AJ - 35
            'Acc 2B',         // AK - 36
            'Tube 2',         // AL - 37
            'Mark 2',         // AM - 38
            'T01',            // AN - 39
            'T02',            // AO - 40
            'T03',            // AP - 41
            'Memory Twist',   // AQ - 42 (for CUTTING_TWIST)
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
     * Get columns that are specific to CUTTING_TWIST type
     */
    public static function getCuttingTwistColumns(): array
    {
        return [
            'Machine Twist',
            'Sequence 2',
            'Barcode Navigasi',
            'Barcode Process',
            'Barcode Shikake',
            'Memory Twist',
        ];
    }

    /**
     * Get columns that are specific to CUTTING type only
     */
    public static function getCuttingOnlyColumns(): array
    {
        return [
            'Barcode Mesin',
            'Gold 1',
            'Gold 2',
            'Acc. 1A',
            'Acc 2A',
        ];
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
