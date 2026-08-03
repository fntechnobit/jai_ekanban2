<?php

namespace App\Imports;

use App\Helpers\ImportHelper;
use App\Models\MasterCircuit;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses the "Stock Opname Barcode Scan History" export from jai_sto_wip
 * (STO Circuit Scan History) and matches each row's CCT Code against the
 * MasterCircuit records of the selected conveyor.
 *
 * Expected layout: title on row 1, STO period on row 2, real headers on row 3,
 * data from row 4 onward. Only CCT Code (col B) and STO (col L) are used.
 */
class CuttingStoImport
{
    protected const HEADER_ROW = 3;
    protected const CCT_CODE_COL = 1;
    protected const STO_COL = 11;
    protected const MAX_ROWS = 5000;

    protected int $conveyorId;
    protected array $notFoundCodes = [];
    protected int $totalRows = 0;

    public function __construct(int $conveyorId)
    {
        $this->conveyorId = $conveyorId;
    }

    /**
     * Parse the file and return matched rows: [['master_circuit_id', 'cct_code', 'cct_no', 'qty'], ...]
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        $headerRow = $worksheet->rangeToArray('A' . self::HEADER_ROW . ':' . $highestColumn . self::HEADER_ROW, null, true, false)[0];
        $this->validateHeaders($headerRow);

        $dataStart = self::HEADER_ROW + 1;
        $this->totalRows = max(0, $highestRow - $dataStart + 1);

        if ($this->totalRows > self::MAX_ROWS) {
            throw new \Exception("Data exceeds " . self::MAX_ROWS . " rows limit. You are trying to upload {$this->totalRows} rows. Please split your data into smaller batches.");
        }

        $grouped = [];
        for ($row = $dataStart; $row <= $highestRow; $row++) {
            $rowData = $worksheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false)[0];

            if ($this->isEmptyRow($rowData)) {
                continue;
            }

            $cctCode = ImportHelper::cleanValue($rowData[self::CCT_CODE_COL] ?? null);
            $sto = ImportHelper::cleanNumeric($rowData[self::STO_COL] ?? null);

            if (!$cctCode || !$sto || $sto <= 0) {
                continue;
            }

            $grouped[$cctCode] = ($grouped[$cctCode] ?? 0) + $sto;
        }

        if (empty($grouped)) {
            return [];
        }

        $circuits = MasterCircuit::where('conveyor_id', $this->conveyorId)
            ->whereIn('cct_code', array_keys($grouped))
            ->get()
            ->keyBy('cct_code');

        $matched = [];
        foreach ($grouped as $cctCode => $qty) {
            $circuit = $circuits->get($cctCode);
            if ($circuit) {
                $matched[] = [
                    'master_circuit_id' => $circuit->id,
                    'cct_code' => $cctCode,
                    'cct_no' => $circuit->cct_no,
                    'qty' => $qty,
                ];
            } else {
                $this->notFoundCodes[] = $cctCode;
            }
        }

        return $matched;
    }

    protected function isEmptyRow($rowData): bool
    {
        return empty(array_filter($rowData, function ($value) {
            return !is_null($value) && $value !== '';
        }));
    }

    protected function validateHeaders(array $headerRow): void
    {
        $cctHeader = strtolower(trim((string) ($headerRow[self::CCT_CODE_COL] ?? '')));
        $stoHeader = strtolower(trim((string) ($headerRow[self::STO_COL] ?? '')));

        if (!str_contains($cctHeader, 'cct') || $stoHeader !== 'sto') {
            throw new \Exception('Invalid template! This file does not match the "Stock Opname Barcode Scan History" export from jai_sto_wip. Expected column B = "CCT Code" and column L = "STO".');
        }
    }

    public function getNotFoundCodes(): array
    {
        return $this->notFoundCodes;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }
}
