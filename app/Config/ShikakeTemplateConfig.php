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
            'Conveyor',           // 0
            'Machine',            // 1
            'QTY',                // 2
            'Issue',              // 3
            'Barcode Kanban',     // 4
            'Family',             // 5
            'Released Note',      // 6
            'Sequence',           // 7
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
            'CCT Code',           // 8
            'CCT No',             // 9
            'Machine Twist',      // 10
            'Sequence 2',         // 11
            'Barcode Navigasi',   // 12
            'Barcode Process',    // 13
            'Barcode Shikake',    // 14
            'To Store',           // 15
            'Cust No',            // 16
            'Kind',               // 17
            'Size',               // 18
            'Color',              // 19
            'CL',                 // 20
            'Terminal A',         // 21
            'Acc 1 A',            // 22
            'Tube A',             // 23
            'Note A',             // 24
            'Strip A',            // 25
            'Mark A',             // 26
            'Terminal B',         // 27
            'Acc 1 AB',           // 28
            'Tube B',             // 29
            'Note B',             // 30
            'Strip B',            // 31
            'Mark B',             // 32
            'Released Date',      // 33
        ]);
    }

    /**
     * Headers for BONDER process type
     */
    public static function getBonderHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Bonder No',          // 8
            'Address',            // 9
            'Dies',               // 10
            'To Machine',         // 11
            'Barcode Navigasi',   // 12
            'Barcode Process',    // 13
            'Released Date',      // 14
            'CCT No A 1',         // 15
            'Bonder No A 1',      // 16
            'CCT No A 2',         // 17
            'Bonder No A 2',      // 18
            'CCT No A 3',         // 19
            'Bonder No A 3',      // 20
            'CCT No A 4',         // 21
            'Bonder No A 4',      // 22
            'CCT No A 5',         // 23
            'Bonder No A 5',      // 24
            'CCT No A 6',         // 25
            'Bonder No A 6',      // 26
            'CCT No A 7',         // 27
            'Bonder No A 7',      // 28
            'CCT No B 1',         // 29
            'Bonder No B 1',      // 30
            'CCT No B 2',         // 31
            'Bonder No B 2',      // 32
            'CCT No B 3',         // 33
            'Bonder No B 3',      // 34
            'CCT No B 4',         // 35
            'Bonder No B 4',      // 36
            'CCT No B 5',         // 37
            'Bonder No B 5',      // 38
            'CCT No B 6',         // 39
            'Bonder No B 6',      // 40
            'CCT No B 7',         // 41
            'Bonder No B 7',      // 42
        ]);
    }

    /**
     * Headers for JOINT process type
     */
    public static function getJointHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Bonder No',          // 8
            'Address',            // 9
            'Address Store',      // 10
            'To Machine',         // 11
            'Barcode Process',    // 12
            'Released Date',      // 13
            'CCT No 1',           // 14
            'Bonder No 1',        // 15
            'CCT No 2',           // 16
            'Bonder No 2',        // 17
            'CCT No 3',           // 18
            'Bonder No 3',        // 19
            'CCT No 4',           // 20
            'Bonder No 4',        // 21
            'CCT No 5',           // 22
            'Bonder No 5',        // 23
        ]);
    }

    /**
     * Headers for SHIELD process type
     */
    public static function getShieldHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Shield No',          // 8
            'Address',            // 9
            'Blade',              // 10
            'CCT No 1',           // 11
            'Bonder No 1',        // 12
            'CCT No 2',           // 13
            'Bonder No 2',        // 14
            'To 1',               // 15
            'To 2',               // 16
            'To 3',               // 17
            'To 4',               // 18
            'To 5',               // 19
            'To 6',               // 20
            'To 7',               // 21
            'To 8',               // 22
            'To 9',               // 23
        ]);
    }

    /**
     * Headers for DBL CRIMP process type
     */
    public static function getDblCrimpHeaders(): array
    {
        return array_merge(self::getBaseHeaders(), [
            'Shield No',          // 8
            'Dbl Crimp',          // 9
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
