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
            'QRCode Drawing',     // 13
            'CCT No A 1',         // 14
            'Bonder No A 1',      // 15
            'CCT No A 2',         // 16
            'Bonder No A 2',      // 17
            'CCT No A 3',         // 18
            'Bonder No A 3',      // 19
            'CCT No A 4',         // 20
            'Bonder No A 4',      // 21
            'CCT No A 5',         // 22
            'Bonder No A 5',      // 23
            'CCT No A 6',         // 24
            'Bonder No A 6',      // 25
            'CCT No A 7',         // 26
            'Bonder No A 7',      // 27
            'CCT No B 1',         // 28
            'Bonder No B 1',      // 29
            'CCT No B 2',         // 30
            'Bonder No B 2',      // 31
            'CCT No B 3',         // 32
            'Bonder No B 3',      // 33
            'CCT No B 4',         // 34
            'Bonder No B 4',      // 35
            'CCT No B 5',         // 36
            'Bonder No B 5',      // 37
            'CCT No B 6',         // 38
            'Bonder No B 6',      // 39
            'CCT No B 7',         // 40
            'Bonder No B 7',      // 41
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
            'QRCode Drawing',     // 12
            'CCT No 1',           // 13
            'Bonder No 1',        // 14
            'CCT No 2',           // 15
            'Bonder No 2',        // 16
            'CCT No 3',           // 17
            'Bonder No 3',        // 18
            'CCT No 4',           // 19
            'Bonder No 4',        // 20
            'CCT No 5',           // 21
            'Bonder No 5',        // 22
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
            'To Machine',         // 10
            'QRCode Drawing',     // 11
            'CCT No 1',           // 12
            'Address 1',          // 13
            'CCT No 2',           // 14
            'Address 2',          // 15
            'To 1',               // 16
            'To 2',               // 17
            'To 3',               // 18
            'To 4',               // 19
            'To 5',               // 20
            'To 6',               // 21
            'To 7',               // 22
            'To 8',               // 23
            'To 9',               // 24
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
            'QRCode Drawing',     // 11
            'CCT No 1',           // 12
            'Address 1',          // 13
            'CCT No 2',           // 14
            'Address 2',          // 15
            'CCT No 3',           // 16
            'Address 3',          // 17
            'CCT No 4',           // 18
            'Address 4',          // 19
            'CCT No 5',           // 20
            'Address 5',          // 21
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
