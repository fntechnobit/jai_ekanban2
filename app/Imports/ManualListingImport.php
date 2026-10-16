<?php

namespace App\Imports;

use App\Config\ManualListingTemplateConfig;
use App\Helpers\ImportHelper;
use App\Http\Requests\ManualListingRequest;
use App\Services\ManualListingService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import listing manual dari Excel.
 *
 * Semua-atau-tidak-sama-sekali: seluruh baris diperiksa lebih dulu, dan bila satu saja
 * salah tidak ada yang disimpan. Listing setengah masuk lebih berbahaya daripada
 * ditolak utuh — jadwal hari itu akan tersusun dari data yang tidak lengkap.
 *
 * Baris dengan kunci listing manual yang sudah ada (tanggal, conveyor, assy code, seq)
 * memperbarui listing itu, sehingga file yang sama aman diimport ulang setelah dikoreksi.
 */
class ManualListingImport
{
    private const MAX_ROWS = 1000;

    public function __construct(private readonly ManualListingService $service)
    {
    }

    /**
     * @return array{success: bool, errors: array, inserted: int, updated: int, unchanged: int,
     *               warnings: array, regenerate: array}
     */
    public function import(string $filePath, int $startRow = 2): array
    {
        // Sheet pertama, bukan sheet aktif: template punya sheet Petunjuk, dan sheet
        // aktif adalah sheet yang terakhir dibuka pengguna sebelum menyimpan file.
        $sheet = IOFactory::load($filePath)->getSheet(0);

        $headers = ManualListingTemplateConfig::getHeaders();
        $last    = chr(ord('A') + count($headers) - 1);

        $this->assertHeaders($sheet->rangeToArray("A1:{$last}1", null, true, false)[0]);

        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow - $startRow + 1 > self::MAX_ROWS) {
            throw new \RuntimeException('Data melebihi ' . self::MAX_ROWS . ' baris. Pecah file menjadi beberapa bagian.');
        }

        $request = new ManualListingRequest();
        $errors  = [];
        $rows    = [];
        $seen    = [];

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $raw = array_map([$this, 'cell'], $sheet->rangeToArray("A{$row}:{$last}{$row}", null, true, false)[0]);

            if (array_filter($raw, fn ($value) => $value !== null) === []) {
                continue;
            }

            try {
                $data = [
                    'listing_date' => $raw[0] === null ? null : ImportHelper::cleanDate($raw[0]),
                    'conveyor'     => $raw[1],
                    'assy'         => $raw[2],
                    'assycode'     => $raw[3],
                    'qty'          => $raw[4],
                    'seq'          => $raw[5],
                    'carline'      => $raw[6],
                    'snp'          => $raw[7],
                    'snpa'         => $raw[8],
                    'plt'          => $raw[9],
                    'mode'         => $this->mode($raw[10]),
                    'remark'       => $raw[11],
                ];

                if ($raw[0] !== null && $data['listing_date'] === null) {
                    throw new \RuntimeException("Tanggal '{$raw[0]}' tidak dikenali, gunakan format DD-MM-YYYY.");
                }

                Validator::make($data, $request->rules(), $request->messages(), $request->attributes())->validate();

                $attributes = $this->service->prepare($data);
                $key        = $this->service->keyOf($attributes);

                if (isset($seen[$key])) {
                    throw new \RuntimeException("sama dengan baris {$seen[$key]} (tanggal, conveyor, assy code & seq sama).");
                }

                $seen[$key] = $row;
                $existing   = $this->service->findByKey($attributes);

                if ($existing && $this->service->isLocked($existing)) {
                    throw new \RuntimeException('listing manual ini sudah diverifikasi atau kanbannya sudah dibuat — unverify dulu untuk mengubahnya.');
                }

                $rows[] = [$attributes, $existing];
            } catch (ValidationException $e) {
                $errors[] = "Baris {$row}: " . implode(' ', Arr::flatten($e->errors()));
            } catch (\Throwable $e) {
                $errors[] = "Baris {$row}: " . $e->getMessage();
            }
        }

        if ($errors === [] && $rows === []) {
            $errors[] = 'Tidak ada baris data yang bisa diimport.';
        }

        $result = [
            'success'    => $errors === [],
            'errors'     => $errors,
            'inserted'   => 0,
            'updated'    => 0,
            'unchanged'  => 0,
            'warnings'   => [],
            'regenerate' => [],
        ];

        if ($errors !== []) {
            return $result;
        }

        DB::transaction(function () use ($rows, &$result) {
            foreach ($rows as [$attributes, $existing]) {
                $saved = $this->service->persist($attributes, $existing);

                $result[match ($saved['status']) {
                    'created' => 'inserted',
                    'updated' => 'updated',
                    default   => 'unchanged',
                }]++;

                $result['warnings']   = array_merge($result['warnings'], $saved['warnings']);
                $result['regenerate'] = array_merge($result['regenerate'], $saved['regenerate']);
            }
        });

        $result['warnings']   = array_values(array_unique($result['warnings']));
        $result['regenerate'] = array_values(array_unique($result['regenerate'], SORT_REGULAR));

        return $result;
    }

    /**
     * Nilai sel apa adanya: kosong menjadi null, teks di-trim, angka bulat dari Excel
     * (dibaca sebagai float) menjadi int supaya lolos aturan `integer`.
     */
    private function cell($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * SEA / AIR (teks) atau angka mode -> angka mode. Kosong = ambil dari acuan.
     */
    private function mode($value)
    {
        if ($value === null || is_int($value)) {
            return $value;
        }

        $map = config('sirep.mapping.mode_map', []);

        return $map[strtolower((string) $value)] ?? $value;
    }

    private function assertHeaders(array $headerRow): void
    {
        $mismatches = [];

        foreach (ManualListingTemplateConfig::getHeaders() as $i => $expected) {
            $found = trim((string) ($headerRow[$i] ?? ''));

            if (strcasecmp($found, $expected) !== 0) {
                $mismatches[] = 'Kolom ' . ImportHelper::numberToColumnLetter($i + 1) . ": seharusnya '{$expected}', ditemukan '{$found}'";
            }
        }

        if ($mismatches !== []) {
            throw new \RuntimeException(
                "Template tidak sesuai:\n" . implode("\n", array_slice($mismatches, 0, 5))
                . "\n\nDownload dan gunakan template Listing Manual."
            );
        }
    }
}
