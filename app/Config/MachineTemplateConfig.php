<?php

namespace App\Config;

/**
 * Centralized configuration for Master Machine import/export templates.
 */
class MachineTemplateConfig
{
    /**
     * Get all Machine headers.
     * Area is not a column here - it is chosen once for the whole import
     * batch (like Circuit's conveyor_id), not per row.
     */
    public static function getHeaders(): array
    {
        return [
            'Machine', // A - 0
            'Type',    // B - 1
        ];
    }
}
