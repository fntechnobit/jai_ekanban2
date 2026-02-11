<?php

namespace App\Config;

/**
 * Centralized configuration for Shikake import/export templates.
 * This class defines headers for each process type, used by both
 * import classes and template generation commands.
 */
class ShikakeTemplateConfig
{
    /**
     * Common base headers shared across all process types
     */
    public static function getBaseHeaders(): array
    {
        return [
            'Carline',            // 0
            'Conveyor',           // 1
            'Machine',            // 2
            'QTY',                // 3
            'Family',             // 4
            'Sequence',       // 5,
            'Released Note',     // 6
        ];
    }

    /**
     * Get the count of base header columns
     */
    public static function getBaseHeaderCount(): int
    {
        return count(self::getBaseHeaders());
    }

    /**
     * Headers for TWIST process type
     */
    public static function getTwistHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'CCT Code',           // 7
            'CCT No',             // 8
            'Machine Twist',      // 9
            'Sequence 2',         // 10
            'Barcode Navigasi',   // 11
            'Barcode Process',    // 12
            'Barcode Shikake',    // 13
            'To Store',           // 14
            'Cust No',            // 15
            'Kind',               // 16
            'Size',               // 17
            'Color',              // 18
            'CL',                 // 19
            'Terminal A',         // 20
            'Acc 1 A',            // 21
            'Tube A',             // 22
            'Note A',             // 23
            'Strip A',            // 24
            'Mark A',             // 25
            'Terminal B',         // 26
            'Acc 1 AB',           // 27
            'Tube B',             // 28
            'Note B',             // 29
            'Strip B',            // 30
            'Mark B',             // 31
        ]);
    }

    /**
     * Headers for BONDER process type
     */
    public static function getBonderHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Bonder No',          // 7
            'Address',            // 8
            'Dies',               // 9
            'To Machine',         // 10
            'Barcode Navigasi',   // 11
            'Barcode Process',    // 12
            'CCT No A 1',         // 13
            'Bonder No A 1',      // 14
            'CCT No A 2',         // 15
            'Bonder No A 2',      // 16
            'CCT No A 3',         // 17
            'Bonder No A 3',      // 18
            'CCT No A 4',         // 19
            'Bonder No A 4',      // 20
            'CCT No A 5',         // 21
            'Bonder No A 5',      // 22
            'CCT No A 6',         // 23
            'Bonder No A 6',      // 24
            'CCT No A 7',         // 25
            'Bonder No A 7',      // 26
            'CCT No B 1',         // 27
            'Bonder No B 1',      // 28
            'CCT No B 2',         // 29
            'Bonder No B 2',      // 30
            'CCT No B 3',         // 31
            'Bonder No B 3',      // 32
            'CCT No B 4',         // 33
            'Bonder No B 4',      // 34
            'CCT No B 5',         // 35
            'Bonder No B 5',      // 36
            'CCT No B 6',         // 37
            'Bonder No B 6',      // 38
            'CCT No B 7',         // 39
            'Bonder No B 7',      // 40
        ]);
    }

    /**
     * Headers for JOINT process type
     */
    public static function getJointHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Bonder No',          // 7
            'Address',            // 8
            'Address Store',      // 9
            'To Machine',         // 10
            'Barcode Process',    // 11
            'CCT No 1',           // 12
            'Bonder No 1',        // 13
            'CCT No 2',           // 14
            'Bonder No 2',        // 15
            'CCT No 3',           // 16
            'Bonder No 3',        // 17
            'CCT No 4',           // 18
            'Bonder No 4',        // 19
            'CCT No 5',           // 20
            'Bonder No 5',        // 21
        ]);
    }

    /**
     * Headers for SHIELD process type
     */
    public static function getShieldHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Shield No',          // 7
            'Address',            // 8
            'Blade',              // 9
            'CCT No 1',           // 10
            'Address 1',          // 11
            'CCT No 2',           // 12
            'Address 2',          // 13
            'To 1',               // 14
            'To 2',               // 15
            'To 3',               // 16
            'To 4',               // 17
            'To 5',               // 18
            'To 6',               // 19
            'To 7',               // 20
            'To 8',               // 21
            'To 9',               // 22
        ]);
    }

    /**
     * Headers for DBL CRIMP process type
     */
    public static function getDblCrimpHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Drawing No',         // 7
            'Address',            // 8
            'Barcode Mesin',      // 9
            'To Machine',         // 10
            'CCT No 1',           // 11
            'Address 1',          // 12
            'CCT No 2',           // 13
            'Address 2',          // 14
            'CCT No 3',           // 15
            'Address 3',          // 16
            'CCT No 4',           // 17
            'Address 4',          // 18
            'CCT No 5',           // 19
            'Address 5',          // 20
        ]);
    }

    /**
     * Get headers by process type
     */
    public static function getHeadersByProcess(string $process): array
    {
        return match ($process) {
            'TWIST' => self::getTwistHeaders(),
            'BONDER' => self::getBonderHeaders(),
            'JOINT' => self::getJointHeaders(),
            'SHIELD' => self::getShieldHeaders(),
            'DBL CRIMP' => self::getDblCrimpHeaders(),
            default => throw new \InvalidArgumentException("Unknown process type: {$process}"),
        };
    }

    /**
     * Get all template configurations for generation
     */
    public static function getAllTemplates(): array
    {
        return [
            'Template_Shikake_Twist.xlsx' => self::getTwistHeaders(),
            'Template_Shikake_Bonder.xlsx' => self::getBonderHeaders(),
            'Template_Shikake_Joint.xlsx' => self::getJointHeaders(),
            'Template_Shikake_Shield.xlsx' => self::getShieldHeaders(),
            'Template_Shikake_Dbl_Crimp.xlsx' => self::getDblCrimpHeaders(),
        ];
    }
}
