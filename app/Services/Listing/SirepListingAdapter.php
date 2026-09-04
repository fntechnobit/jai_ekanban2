<?php

namespace App\Services\Listing;

use Carbon\Carbon;

/**
 * Menormalkan satu baris dari API SIREP menjadi bentuk kolom listing_stage.
 *
 * Seluruh perbedaan penamaan dan aturan konversi antara SIREP baru dan struktur
 * internal e-Kanban dikumpulkan di satu kelas ini, sehingga perubahan kontrak API
 * cukup ditangani di sini.
 *
 * Pemetaan field:
 *   id                    -> id_listing
 *   date                  -> listing_date_time
 *   conveyor              -> conveyor        (di-trim)
 *   assy_code             -> assycode
 *   assy_number           -> assy            (kunci pemetaan master circuit/shikake)
 *   quantity              -> qty
 *   seq                   -> seq
 *   carline               -> carline
 *   standard_packing_*    -> snp / snpa      (lihat config sirep.mapping)
 *   max_pallet_*          -> plt
 *   shipping_method       -> mode            (lewat tabel konversi)
 *   (tidak ada)           -> shift = 0
 *
 * Catatan tentang `shift`: API SIREP tidak menyediakannya, dan itu tidak menjadi
 * masalah — jumlah shift diturunkan per tanggal dari total qty listing dan flag
 * `is_overtime`, bukan dari baris listing satuan. Kolom staging diisi 0.
 */
class SirepListingAdapter
{
    /**
     * @param  array<string, mixed>  $row  Baris mentah dari API
     * @return array<string, mixed>        Siap disimpan ke listing_stage
     */
    public function toStageAttributes(array $row): array
    {
        $shippingMethod = strtolower(trim((string) ($row['shipping_method'] ?? '')));
        $useAir         = config('sirep.mapping.use_shipping_method', true) && $shippingMethod === 'air';

        // Nilai utama mengikuti metode pengiriman; nilai alternatif adalah pasangannya.
        $packingPrimary   = $useAir ? ($row['standard_packing_air'] ?? null) : ($row['standard_packing_sea'] ?? null);
        $packingAlternate = $useAir ? ($row['standard_packing_sea'] ?? null) : ($row['standard_packing_air'] ?? null);
        $palletPrimary    = $useAir ? ($row['max_pallet_air'] ?? null) : ($row['max_pallet_sea'] ?? null);

        return [
            'id_listing'        => $this->toInt($row['id'] ?? null),
            'source'            => 'api',
            'listing_date_time' => $this->toDate($row['date'] ?? null),
            'conveyor'          => trim((string) ($row['conveyor'] ?? '')),
            'shift'             => 0,
            'assycode'          => trim((string) ($row['assy_code'] ?? '')),
            'assy'              => trim((string) ($row['assy_number'] ?? '')),
            'carline'           => trim((string) ($row['carline'] ?? '')),
            'qty'               => $this->toInt($row['quantity'] ?? null),
            'seq'               => $this->toInt($row['seq'] ?? null),
            'plt'               => $this->toInt($palletPrimary),
            'mode'              => $this->toMode($shippingMethod),
            // Penanda dari SIREP bahwa hari itu ada CO5 / kapasitas over.
            // Tidak memengaruhi alokasi; dipakai sebagai pemeriksa silang saat generate.
            'is_overtime'       => (bool) ($row['is_overtime'] ?? false),
            'snp'               => $this->toInt($packingPrimary),
            'snpa'              => $this->toInt($packingAlternate),
        ];
    }

    /**
     * Kunci identitas baris, sama dengan kunci deduplikasi yang dipakai
     * sinkronisasi lama: tanggal + conveyor + assycode + seq.
     *
     * @param  array<string, mixed>  $attributes  Hasil toStageAttributes()
     */
    public function keyOf(array $attributes): string
    {
        return implode('|', [
            Carbon::parse($attributes['listing_date_time'])->format('Y-m-d'),
            trim((string) $attributes['conveyor']),
            trim((string) $attributes['assycode']),
            (int) $attributes['seq'],
        ]);
    }

    /**
     * Sidik jari isi baris — dipakai untuk mendeteksi revisi.
     *
     * API SIREP belum menyediakan `updated_at` maupun `revision`, sehingga satu-satunya
     * cara mengetahui sebuah baris berubah adalah membandingkan isinya. Field yang
     * diikutkan adalah field yang dapat direvisi tim PPC dan berpengaruh ke jadwal
     * maupun jumlah kanban.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function fingerprintOf(array $attributes): string
    {
        return md5(implode('|', [
            (int) $attributes['qty'],
            (string) $attributes['assy'],
            (string) $attributes['carline'],
            (int) $attributes['snp'],
            (int) $attributes['snpa'],
            (int) $attributes['plt'],
            (int) $attributes['mode'],
            (int) $attributes['id_listing'],
        ]));
    }

    /**
     * Sidik jari untuk baris yang sudah tersimpan di staging, dihitung dengan
     * aturan yang sama agar dapat dibandingkan langsung.
     */
    public function fingerprintOfModel(\App\Models\ListingStage $stage): string
    {
        return $this->fingerprintOf([
            'qty'        => $stage->qty,
            'assy'       => $stage->assy,
            'carline'    => $stage->carline,
            'snp'        => $stage->snp,
            'snpa'       => $stage->snpa,
            'plt'        => $stage->plt,
            'mode'       => $stage->mode,
            'id_listing' => $stage->id_listing,
        ]);
    }

    private function toMode(string $shippingMethod): int
    {
        $map = config('sirep.mapping.mode_map', []);

        return (int) ($map[$shippingMethod] ?? config('sirep.mapping.mode_default', 0));
    }

    private function toInt($value): int
    {
        return $value === null || $value === '' ? 0 : (int) $value;
    }

    private function toDate($value): string
    {
        return $value
            ? Carbon::parse($value)->startOfDay()->format('Y-m-d H:i:s')
            : Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
    }
}
