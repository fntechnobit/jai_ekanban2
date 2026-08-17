<?php

namespace App\Services\Listing;

use App\Models\MasterConveyor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Mengambil listing dari REST API SIREP.
 *
 * Dua batasan API yang ditangani di sini:
 *
 *  1. Parameter `conveyor` hanya menerima satu nilai, sedangkan sinkronisasi
 *     kami berbasis rentang tanggal untuk seluruh conveyor. Karena itu daftar
 *     conveyor diambil dari master lokal lalu dipanggil satu per satu secara
 *     paralel.
 *
 *  2. API belum menyediakan paginasi maupun jumlah total. Rentang tanggal
 *     dipecah menjadi jendela beberapa hari agar ukuran tiap respons kecil dan
 *     risiko terpotong diam-diam berkurang.
 */
class ApiListingSource implements ListingSourceInterface
{
    public function __construct(
        private readonly SirepApiClient $client,
        private readonly SirepListingAdapter $adapter,
    ) {
    }

    public function name(): string
    {
        return 'api';
    }

    public function ping(): array
    {
        return $this->client->ping();
    }

    public function fetch(string $startDate, string $endDate, array $conveyorCodes = []): ListingFetchResult
    {
        $conveyors = $conveyorCodes ?: $this->activeConveyorCodes();

        if ($conveyors === []) {
            return new ListingFetchResult(
                rows: [],
                errors: ['Tidak ada conveyor aktif di master data — tidak ada yang dapat disinkronkan.'],
                scopeConveyors: [],
            );
        }

        $requests = $this->buildRequests($conveyors, $startDate, $endDate);
        $result   = $this->client->fetchListingBatch($requests);

        // Conveyor yang salah satu jendela tanggalnya gagal TIDAK boleh direkonsiliasi:
        // datanya tidak lengkap, dan baris yang tidak muncul bukan berarti dibatalkan.
        $failedConveyors = $this->failedConveyors($result['errors'], $conveyors);
        $okConveyors     = array_values(array_diff($conveyors, $failedConveyors));

        $rows = [];

        foreach ($result['rows'] as $conveyor => $apiRows) {
            if (in_array($conveyor, $failedConveyors, true)) {
                continue;
            }

            foreach ($apiRows as $apiRow) {
                $attributes = $this->adapter->toStageAttributes($apiRow);

                if ($attributes['assycode'] === '' && $attributes['assy'] === '') {
                    continue; // baris tanpa identitas assy tidak dapat dijadwalkan
                }

                $rows[] = $attributes;
            }
        }

        // Urutkan agar id auto-increment listing_stage mencerminkan urutan alokasi FIFO.
        // seq dipakai sebagai kunci utama karena kestabilan `id` dari SIREP baru
        // belum dijamin; `id` hanya menjadi pemecah seri.
        usort($rows, function (array $a, array $b) {
            return [$a['listing_date_time'], $a['conveyor'], $a['seq'], $a['id_listing']]
               <=> [$b['listing_date_time'], $b['conveyor'], $b['seq'], $b['id_listing']];
        });

        Log::info('SIREP API listing fetched', [
            'range'     => "{$startDate}..{$endDate}",
            'conveyors' => count($okConveyors),
            'failed'    => $failedConveyors,
            'rows'      => count($rows),
        ]);

        return new ListingFetchResult(
            rows: $rows,
            errors: $result['errors'],
            scopeConveyors: $okConveyors,
        );
    }

    /**
     * Daftar conveyor aktif dari master lokal.
     *
     * Nilai `sirep_conveyor_code` dipakai bila nama di SIREP berbeda dengan nama
     * master; bila kolom tersebut belum ada, nama master dipakai apa adanya.
     *
     * @return array<int, string>
     */
    private function activeConveyorCodes(): array
    {
        return MasterConveyor::query()
            ->pluck('conveyor')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Susun daftar permintaan: setiap conveyor × setiap jendela tanggal.
     *
     * @param  array<int, string>  $conveyors
     * @return array<int, array{conveyor: string, start: string, end: string}>
     */
    private function buildRequests(array $conveyors, string $startDate, string $endDate): array
    {
        $requests = [];

        foreach ($this->dateWindows($startDate, $endDate) as $window) {
            foreach ($conveyors as $conveyor) {
                $requests[] = [
                    'conveyor' => $conveyor,
                    'start'    => $window['start'],
                    'end'      => $window['end'],
                ];
            }
        }

        return $requests;
    }

    /**
     * Pecah rentang tanggal menjadi jendela selebar `chunk_days` hari.
     *
     * @return array<int, array{start: string, end: string}>
     */
    private function dateWindows(string $startDate, string $endDate): array
    {
        $chunkDays = max(1, (int) config('sirep.api.chunk_days', 7));
        $cursor    = Carbon::parse($startDate)->startOfDay();
        $end       = Carbon::parse($endDate)->startOfDay();
        $windows   = [];

        while ($cursor->lte($end)) {
            $windowEnd = $cursor->copy()->addDays($chunkDays - 1)->min($end);

            $windows[] = [
                'start' => $cursor->format('Y-m-d'),
                'end'   => $windowEnd->format('Y-m-d'),
            ];

            $cursor = $windowEnd->copy()->addDay();
        }

        return $windows;
    }

    /**
     * Tentukan conveyor mana yang datanya tidak lengkap, dari pesan kegagalan.
     *
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $conveyors
     * @return array<int, string>
     */
    private function failedConveyors(array $errors, array $conveyors): array
    {
        $failed = [];

        foreach ($conveyors as $conveyor) {
            foreach ($errors as $error) {
                if (str_contains($error, $conveyor . ' (')) {
                    $failed[] = $conveyor;
                    break;
                }
            }
        }

        return $failed;
    }
}
