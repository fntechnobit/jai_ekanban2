<?php

namespace App\Enums;

enum ProcessType: string
{
    case BONDER = 'BONDER';
    case DBL_CRIMP = 'DBL CRIMP';
    case JOINT = 'JOINT';
    case SHIELD = 'SHIELD';

    /**
     * Get all process types as array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get process types for select dropdown
     */
    public static function forSelect(): array
    {
        $options = [];
        foreach (self::cases() as $process) {
            $options[] = [
                'value' => $process->value,
                'label' => $process->value,
            ];
        }
        return $options;
    }

    /**
     * Get the label for display
     */
    public function label(): string
    {
        return $this->value;
    }
}