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
            'Sequence',           // 5
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
            'CCT Code',           // 6
            'CCT No',             // 7
            'Machine Twist',      // 8
            'Sequence 2',         // 9
            'Barcode Navigasi',   // 10
            'Barcode Process',    // 11
            'Barcode Shikake',    // 12
            'To Store',           // 13
            'Cust No',            // 14
            'Kind',               // 15
            'Size',               // 16
            'Color',              // 17
            'CL',                 // 18
            'Terminal A',         // 19
            'Acc 1 A',            // 20
            'Tube A',             // 21
            'Note A',             // 22
            'Strip A',            // 23
            'Mark A',             // 24
            'Terminal B',         // 25
            'Acc 1 AB',           // 26
            'Tube B',             // 27
            'Note B',             // 28
            'Strip B',            // 29
            'Mark B',             // 30
        ]);
    }

    /**
     * Headers for BONDER process type
     */
    public static function getBonderHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Bonder No',          // 6
            'Address',            // 7
            'Dies',               // 8
            'To Machine',         // 9
            'Barcode Navigasi',   // 10
            'Barcode Process',    // 11
            'CCT No A 1',         // 12
            'Bonder No A 1',      // 13
            'CCT No A 2',         // 14
            'Bonder No A 2',      // 15
            'CCT No A 3',         // 16
            'Bonder No A 3',      // 17
            'CCT No A 4',         // 18
            'Bonder No A 4',      // 19
            'CCT No A 5',         // 20
            'Bonder No A 5',      // 21
            'CCT No A 6',         // 22
            'Bonder No A 6',      // 23
            'CCT No A 7',         // 24
            'Bonder No A 7',      // 25
            'CCT No B 1',         // 26
            'Bonder No B 1',      // 27
            'CCT No B 2',         // 28
            'Bonder No B 2',      // 29
            'CCT No B 3',         // 30
            'Bonder No B 3',      // 31
            'CCT No B 4',         // 32
            'Bonder No B 4',      // 33
            'CCT No B 5',         // 34
            'Bonder No B 5',      // 35
            'CCT No B 6',         // 36
            'Bonder No B 6',      // 37
            'CCT No B 7',         // 38
            'Bonder No B 7',      // 39
        ]);
    }

    /**
     * Headers for JOINT process type
     */
    public static function getJointHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Bonder No',          // 6
            'Address',            // 7
            'Address Store',      // 8
            'To Machine',         // 9
            'Barcode Process',    // 10
            'CCT No 1',           // 11
            'Bonder No 1',        // 12
            'CCT No 2',           // 13
            'Bonder No 2',        // 14
            'CCT No 3',           // 15
            'Bonder No 3',        // 16
            'CCT No 4',           // 17
            'Bonder No 4',        // 18
            'CCT No 5',           // 19
            'Bonder No 5',        // 20
        ]);
    }

    /**
     * Headers for SHIELD process type
     */
    public static function getShieldHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Shield No',          // 6
            'Address',            // 7
            'Blade',              // 8
            'CCT No 1',           // 9
            'Bonder No 1',        // 10
            'CCT No 2',           // 11
            'Bonder No 2',        // 12
            'To 1',               // 13
            'To 2',               // 14
            'To 3',               // 15
            'To 4',               // 16
            'To 5',               // 17
            'To 6',               // 18
            'To 7',               // 19
            'To 8',               // 20
            'To 9',               // 21
        ]);
    }

    /**
     * Headers for DBL CRIMP process type
     */
    public static function getDblCrimpHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Drawing No',         // 6
            'Address',            // 7
            'Barcode Mesin',      // 8
            'To Machine',         // 9
            'CCT No 1',           // 10
            'Address 1',          // 11
            'CCT No 2',           // 12
            'Address 2',          // 13
            'CCT No 3',           // 14
            'Address 3',          // 15
            'CCT No 4',           // 16
            'Address 4',          // 17
            'CCT No 5',           // 18
            'Address 5',          // 19
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
