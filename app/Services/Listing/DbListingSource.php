<?php

namespace App\Services\Listing;

use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mengambil listing langsung dari database SIREP lama.
 *
 * Ini adalah perilaku asli jai_ekanban, dipertahankan sebagai jalur cadangan.
 * Aktif bila config `sirep.listing_source` bernilai 'db'.
 */
class DbListingSource implements ListingSourceInterface
{
    public function name(): string
    {
        return 'db';
    }

    public function ping(): array
    {
        try {
            DB::connection('mysql_listing')->getPdo();

            return ['ok' => true, 'message' => 'Database listing (SIREP lama) dapat dihubungi'];
        } catch (\Throwable $e) {
            return [
                'ok'      => false,
                'message' => 'Tidak dapat terhubung ke database listing (PPC): ' . $e->getMessage(),
            ];
        }
    }

    public function fetch(string $startDate, string $endDate, array $conveyorCodes = []): ListingFetchResult
    {
        $from = Carbon::parse($startDate)->startOfDay();
        $to   = Carbon::parse($endDate)->endOfDay();

        $query = Listing::whereBetween('time', [$from, $to]);

        if ($conveyorCodes !== []) {
            $query->whereIn('cv', $conveyorCodes);
        }

        // Urutan id_listing menentukan urutan alokasi FIFO ke cutoff CO1–CO5.
        $listings = $query->orderBy('id_listing', 'asc')->get();

        $rows = $listings->map(fn ($listing) => [
            'id_listing'        => (int) $listing->id_listing,
            'listing_date_time' => Carbon::parse($listing->time)->format('Y-m-d H:i:s'),
            'conveyor'          => trim((string) ($listing->cv ?? '')),
            'shift'             => (int) ($listing->shift ?? 0),
            'assycode'          => trim((string) ($listing->assycode ?? '')),
            'assy'              => trim((string) ($listing->assy ?? '')),
            'carline'           => trim((string) ($listing->carline ?? '')),
            'qty'               => (int) ($listing->qty ?? 0),
            'seq'               => (int) ($listing->seq ?? 0),
            'plt'               => (int) ($listing->plt ?? 0),
            'mode'              => (int) ($listing->mode ?? 0),
            'snp'               => (int) ($listing->snp ?? 0),
            'snpa'              => (int) ($listing->snpa ?? 0),
        ])->all();

        // scopeConveyors = null: seluruh rentang terambil tanpa pembatasan conveyor.
        return new ListingFetchResult(
            rows: $rows,
            errors: [],
            scopeConveyors: $conveyorCodes !== [] ? $conveyorCodes : null,
        );
    }
}
