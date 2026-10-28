<?php

namespace App\Imports;

use App\Helpers\ImportHelper;
use App\Models\MasterShikake;

/**
 * Reads the same "Stock Opname Barcode Scan History" export as
 * CuttingStoImport, but takes the drawing (shikake) kanban scans instead of
 * the single-circuit ones, and matches each Drawing code against the shikake
 * code printed on the kanban (MasterShikake::CODE_COLUMNS) of the selected
 * conveyor.
 *
 * jai_sto_wip writes one scan of a drawing kanban as one row PER CCT in that
 * drawing, each carrying the full STO qty, so rows of one scan are collapsed
 * before summing. Single-circuit scans have Drawing = "SINGLE" and are skipped.
 */
class ShikakeStoImport extends CuttingStoImport
{
    protected const DRAWING_COL = 2;
    protected const AREA_COL = 3;
    protected const KANBAN_COL = 4;
    protected const ISSUE_COL = 9;
    protected const NIK_COL = 13;
    protected const DATE_COL = 14;
    protected const SINGLE_DRAWING = 'SINGLE';

    /**
     * Parse the file and return matched rows: [['master_shikake_id', 'code', 'process', 'machine', 'qty'], ...]
     */
    public function parse(string $filePath): array
    {
        $scans = [];
        foreach ($this->readRows($filePath) as $rowData) {
            $drawing = ImportHelper::cleanValue($rowData[self::DRAWING_COL] ?? null);
            $sto = ImportHelper::cleanNumeric($rowData[self::STO_COL] ?? null);

            if (!$drawing || strcasecmp($drawing, self::SINGLE_DRAWING) === 0 || !$sto || $sto <= 0) {
                continue;
            }

            $kanban = ImportHelper::cleanValue($rowData[self::KANBAN_COL] ?? null);
            $scanKey = implode('|', [
                strtoupper($drawing),
                $rowData[self::AREA_COL] ?? '',
                $kanban ?? $rowData[self::DATE_COL] ?? '',
                $rowData[self::ISSUE_COL] ?? '',
                $rowData[self::NIK_COL] ?? '',
            ]);

            $scans[$scanKey] = [
                'code'    => $drawing,
                'carline' => self::carlineOf($kanban),
                'qty'     => $sto,
            ];
        }

        if (empty($scans)) {
            return [];
        }

        $candidates = $this->shikakesByCode(array_unique(array_column($scans, 'code')));

        $matched = [];
        $notFound = [];
        foreach ($scans as $scan) {
            $found = $candidates[strtoupper($scan['code'])] ?? [];

            // Mirrored families can share one shikake code on a conveyor; the
            // carline in the scanned barcode tells them apart.
            if (count($found) > 1 && $scan['carline'] !== null) {
                $found = array_filter($found, fn ($s) => strcasecmp((string) $s->carline, $scan['carline']) === 0) ?: $found;
            }

            if (count($found) !== 1) {
                $notFound[$scan['code']] = empty($found)
                    ? $scan['code']
                    : "{$scan['code']} (matches " . count($found) . ' shikake)';
                continue;
            }

            $shikake = reset($found);
            $matched[$shikake->id] ??= [
                'master_shikake_id' => $shikake->id,
                'code'              => $shikake->shikake_code,
                'process'           => $shikake->process,
                'machine'           => $shikake->machine,
                'qty'               => 0,
            ];
            $matched[$shikake->id]['qty'] += $scan['qty'];
        }

        $this->notFoundCodes = array_values($notFound);

        return array_values($matched);
    }

    /**
     * Shikakes of the conveyor whose kanban code is one of $codes, as
     * [UPPER(code) => [id => row]].
     */
    protected function shikakesByCode(array $codes): array
    {
        $byCode = [];
        foreach (MasterShikake::CODE_COLUMNS as $process => [$table, $column]) {
            $rows = MasterShikake::query()
                ->join($table, "{$table}.master_shikake_id", '=', 'master_shikake.id')
                ->where('master_shikake.conveyor_id', $this->conveyorId)
                ->where('master_shikake.process', $process)
                ->whereIn("{$table}.{$column}", $codes)
                ->get([
                    'master_shikake.id',
                    'master_shikake.process',
                    'master_shikake.machine',
                    'master_shikake.carline',
                    "{$table}.{$column} as shikake_code",
                ]);

            foreach ($rows as $row) {
                $byCode[strtoupper(MasterShikake::normalizeCode($row->shikake_code))][$row->id] = $row;
            }
        }

        return $byCode;
    }

    /**
     * Carline of a drawing kanban barcode (carline.code.issue.qty.nys), or null
     * when the barcode is not in that form. The code itself may contain dots,
     * so only the first segment is relied on.
     */
    protected static function carlineOf(?string $kanban): ?string
    {
        $parts = explode('.', (string) $kanban);

        return count($parts) >= 5 && $parts[0] !== '' ? $parts[0] : null;
    }

    protected function validateHeaders(array $headerRow): void
    {
        parent::validateHeaders($headerRow);

        $drawingHeader = strtolower(trim((string) ($headerRow[self::DRAWING_COL] ?? '')));
        if (!str_contains($drawingHeader, 'drawing')) {
            throw new \Exception('Invalid template! This file does not match the "Stock Opname Barcode Scan History" export from jai_sto_wip. Expected column C = "Drawing".');
        }
    }
}
