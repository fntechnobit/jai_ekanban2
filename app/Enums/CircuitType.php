<?php

namespace App\Enums;

/**
 * Process type of a master circuit row (master_circuit.type).
 *
 * - CUTTING        : 1 circuit = 1 kanban, tiket cutting standar
 * - CUTTING_TWIST  : 1 circuit = 1 kanban, mesin cutting + mesin twist terpisah
 * - CUTTING_EJ     : tiket cutting + QR EJ-A / EJ-B per sisi
 * - CUTTING_JAM    : 1 baris master = 1 PASANG circuit (dipotong & di-twist di mesin JAM),
 *                    circuit ke-2 disimpan di kolom *_2 pada baris yang sama
 */
enum CircuitType: string
{
    case CUTTING = 'CUTTING';
    case CUTTING_TWIST = 'CUTTING_TWIST';
    case CUTTING_EJ = 'CUTTING_EJ';
    case CUTTING_JAM = 'CUTTING_JAM';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Resolve a raw value (from Excel / DB) to a type; unknown or empty falls back to CUTTING.
     */
    public static function fromRaw(?string $value): self
    {
        return self::tryFromInput($value) ?? self::CUTTING;
    }

    /**
     * Resolve a type typed by a user (Excel import): accepts the enum value, the
     * dropdown label or the short label, with spaces / hyphens in place of the
     * underscore ("CUTTING EJ", "cutting-ej", "EJ", "JAM TWIST"). Null when unknown.
     */
    public static function tryFromInput(?string $value): ?self
    {
        $key = self::normalize((string) $value);
        if ($key === '') {
            return null;
        }

        foreach (self::cases() as $case) {
            if (in_array($key, [$case->value, self::normalize($case->label()), $case->shortLabel()], true)) {
                return $case;
            }
        }

        return null;
    }

    private static function normalize(string $value): string
    {
        return trim(preg_replace('/[\s\x{00A0}\-_]+/u', '_', strtoupper($value)), '_');
    }

    /** Label for dropdowns */
    public function label(): string
    {
        return match ($this) {
            self::CUTTING => 'CUTTING',
            self::CUTTING_TWIST => 'CUTTING TWIST',
            self::CUTTING_EJ => 'CUTTING EJ',
            self::CUTTING_JAM => 'JAM TWIST',
        };
    }

    /** Short code shown in badges and reports */
    public function shortLabel(): string
    {
        return match ($this) {
            self::CUTTING => 'CCT',
            self::CUTTING_TWIST => 'TWS',
            self::CUTTING_EJ => 'EJ',
            self::CUTTING_JAM => 'JAM',
        };
    }

    /** Whether one kanban of this type carries two circuits */
    public function isPair(): bool
    {
        return $this === self::CUTTING_JAM;
    }
}
