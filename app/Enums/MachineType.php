<?php

namespace App\Enums;

enum MachineType: string
{
    case BONDER = 'BONDER';
    case DBL_CRIMP = 'DBL CRIMP';
    case JOINT = 'JOINT';
    case SHIELD = 'SHIELD';
    case CUTTING = 'CUTTING';
    case TWIST = 'TWIST';

    /**
     * Get all machine types as array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get machine types for select dropdown
     */
    public static function forSelect(): array
    {
        $options = [];
        foreach (self::cases() as $type) {
            $options[] = [
                'value' => $type->value,
                'label' => $type->value,
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
