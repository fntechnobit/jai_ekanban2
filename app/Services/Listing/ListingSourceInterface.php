<?php

namespace App\Services\Listing;

/**
 * Kontrak sumber data listing.
 *
 * Dua implementasi tersedia:
 *   - ApiListingSource : REST API SIREP        (mode utama jai_ekanban2)
 *   - DbListingSource  : database SIREP lama   (jalur cadangan, perilaku jai_ekanban)
 *
 * Pemilihan dilakukan lewat config `sirep.listing_source`, sehingga peralihan
 * maupun pengembalian cukup dengan mengubah satu nilai di .env.
 */
interface ListingSourceInterface
{
    /**
     * Ambil listing untuk satu rentang tanggal.
     *
     * @param  string  $startDate  Format Y-m-d
     * @param  string  $endDate    Format Y-m-d, inklusif
     * @param  array<int, string>  $conveyorCodes  Kosong = seluruh conveyor aktif
     */
    public function fetch(string $startDate, string $endDate, array $conveyorCodes = []): ListingFetchResult;

    /**
     * Uji ketersediaan sumber sebelum sinkronisasi dijalankan.
     *
     * @return array{ok: bool, message: string}
     */
    public function ping(): array;

    /**
     * Nama sumber untuk keperluan pesan dan log: 'api' atau 'db'.
     */
    public function name(): string;
}
