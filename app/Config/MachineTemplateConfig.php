<?php

namespace App\Config;

/**
 * Centralized configuration for Master Machine import/export templates.
 */
class MachineTemplateConfig
{
    /**
     * Get all Machine headers
     */
    public static function getHeaders(): array
    {
        return [
            'Machine', // A - 0
            'Type',    // B - 1
            'Area',    // C - 2
        ];
    }
}
