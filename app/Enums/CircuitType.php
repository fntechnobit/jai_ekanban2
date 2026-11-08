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
 * - CUTTING_MINICOAX       : kabel minicoax, ACC1-ACC4 per sisi + barcode proses A/B
 * - CUTTING_ANTENA_FEEDER  : kabel antena feeder, tiket instruksi dengan Kode & Part No
 * - CUTTING_BATTERY_CABLE  : battery cable, QR mesin + QR drawing + tujuan TO 1-9
 */
enum CircuitType: string
{
    case CUTTING = 'CUTTING';
    case CUTTING_TWIST = 'CUTTING_TWIST';
    case CUTTING_EJ = 'CUTTING_EJ';
    case CUTTING_JAM = 'CUTTING_JAM';
    case CUTTING_MINICOAX = 'CUTTING_MINICOAX';
    case CUTTING_ANTENA_FEEDER = 'CUTTING_ANTENA_FEEDER';
    case CUTTING_BATTERY_CABLE = 'CUTTING_BATTERY_CABLE';

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
     * dropdown label, the short label or an alias, with spaces / hyphens in place
     * of the underscore ("CUTTING EJ", "cutting-ej", "EJ", "JAM TWIST",
     * "Battery Cable", "ANTENNA"). Null when unknown.
     */
    public static function tryFromInput(?string $value): ?self
    {
        $key = self::normalize((string) $value);
        if ($key === '') {
            return null;
        }

        foreach (self::cases() as $case) {
            $accepted = array_merge([$case->value, self::normalize($case->label()), $case->shortLabel()], $case->aliases());
            if (in_array($key, $accepted, true)) {
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
            self::CUTTING_MINICOAX => 'MINICOAX',
            self::CUTTING_ANTENA_FEEDER => 'ANTENA FEEDER',
            self::CUTTING_BATTERY_CABLE => 'BATTERY CABLE',
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
            self::CUTTING_MINICOAX => 'MCX',
            self::CUTTING_ANTENA_FEEDER => 'ANT',
            self::CUTTING_BATTERY_CABLE => 'BC',
        };
    }

    /**
     * Ejaan lain yang diterima dari kolom Type di Excel (sudah dinormalisasi:
     * huruf besar, spasi/strip menjadi underscore).
     */
    private function aliases(): array
    {
        return match ($this) {
            self::CUTTING_MINICOAX => ['MINI_COAX', 'CUTTING_MINI_COAX'],
            self::CUTTING_ANTENA_FEEDER => ['ANTENA', 'ANTENNA', 'ANTENNA_FEEDER', 'CUTTING_ANTENNA_FEEDER'],
            self::CUTTING_BATTERY_CABLE => ['BATTERY', 'CUTTING_BATTERY'],
            default => [],
        };
    }

    /** Whether one kanban of this type carries two circuits */
    public function isPair(): bool
    {
        return $this === self::CUTTING_JAM;
    }

    /**
     * Type yang tiketnya memakai kolom tambahan BM-BX (ACC3/ACC4, barcode proses B,
     * Kode/Part No, Qty DC, TO 7-9) - lihat CircuitTemplateConfig::getExtensionHeaderGroups().
     */
    public function usesSpecialColumns(): bool
    {
        return in_array($this, [self::CUTTING_MINICOAX, self::CUTTING_ANTENA_FEEDER, self::CUTTING_BATTERY_CABLE], true);
    }
}
