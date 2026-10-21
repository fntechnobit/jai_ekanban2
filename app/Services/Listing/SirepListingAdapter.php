<?php

namespace App\Services\Listing;

use App\Models\ListingStage;
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
     * Field isi baris yang dicerminkan dari SIREP. Perubahan pada salah satunya berarti
     * PPC merevisi baris itu, dan jadwal yang belum terkunci harus disusun ulang.
     *
     * API SIREP belum menyediakan `updated_at` maupun `revision`, sehingga satu-satunya
     * cara mengetahui sebuah baris berubah adalah membandingkan isinya.
     */
    public const CONTENT_FIELDS = ['seq', 'assycode', 'assy', 'qty', 'carline', 'snp', 'snpa', 'plt', 'mode'];

    /** Field yang dibandingkan sebagai angka; sisanya teks. */
    private const NUMERIC_FIELDS = ['seq', 'qty', 'snp', 'snpa', 'plt', 'mode'];

    /**
     * Kunci identitas baris: id SIREP (`id_listing`) pada tanggal × conveyor yang sama.
     *
     * seq dan assy code SENGAJA tidak ikut. PPC mengubah urutan produksi di SIREP beberapa
     * hari sebelum hari-H (B3-ENG 26-10-2026: 7YA1B seq 4 → 1, id tetap 9526). Dengan seq
     * di dalam kunci, perubahan itu terbaca sebagai "baris lama dibatalkan + baris baru":
     * bila jadwalnya sudah diverifikasi baris lama tidak boleh dihapus, sehingga listing
     * menjadi dobel dan generate menjadwalkan duplikatnya di shift lain. Dengan id sebagai
     * kunci, perubahan seq cukup memperbarui baris yang sama.
     *
     * Tanggal dan conveyor tetap bagian kunci: listing yang dipindah PPC ke tanggal atau
     * conveyor lain diperlakukan sebagai baris baru di tempat barunya, dan baris lamanya
     * mengikuti aturan pembatalan (dihapus bila belum terkunci).
     *
     * Baris tanpa id memakai kunci lama: tanggal + conveyor + assycode + seq.
     *
     * @param  array<string, mixed>  $attributes  Hasil toStageAttributes(), atau kolom staging
     */
    public function keyOf(array $attributes): string
    {
        $tanggal  = Carbon::parse($attributes['listing_date_time'])->format('Y-m-d');
        $conveyor = trim((string) $attributes['conveyor']);
        $id       = (int) ($attributes['id_listing'] ?? 0);

        // Sumber ikut di kunci: id SIREP baru (api) dan id database SIREP lama (db) adalah
        // dua deret angka berbeda yang bisa kebetulan sama.
        if ($id > 0) {
            return implode('|', ['id', $this->sourceOf($attributes), $tanggal, $conveyor, $id]);
        }

        return implode('|', [
            $tanggal,
            $conveyor,
            trim((string) $attributes['assycode']),
            (int) $attributes['seq'],
        ]);
    }

    /**
     * Kunci sebuah baris staging, dihitung dengan aturan yang sama dengan baris masuk.
     */
    public function keyOfModel(ListingStage $stage): string
    {
        return $this->keyOf([
            'listing_date_time' => $stage->listing_date_time,
            'conveyor'          => $stage->conveyor,
            'assycode'          => $stage->assycode,
            'seq'               => $stage->seq,
            'id_listing'        => $stage->id_listing,
            'source'            => $stage->source,
        ]);
    }

    /**
     * Sumber sebuah baris. Baris dari DbListingSource tidak membawa kolom `source`,
     * dan kolom staging-nya berbawaan 'db'.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function sourceOf(array $attributes): string
    {
        return (string) ($attributes['source'] ?? 'db') ?: 'db';
    }

    /**
     * Field isi yang berbeda antara baris staging dan baris dari SIREP.
     *
     * @param  array<string, mixed>  $attributes  Hasil toStageAttributes()
     * @return array<string, array{0: mixed, 1: mixed}>  field => [nilai staging, nilai SIREP]
     */
    public function changedFields(ListingStage $stage, array $attributes): array
    {
        $berubah = [];

        foreach (self::CONTENT_FIELDS as $field) {
            $lama = $stage->{$field};
            $baru = $attributes[$field] ?? null;

            $sama = in_array($field, self::NUMERIC_FIELDS, true)
                ? (int) $lama === (int) $baru
                : trim((string) $lama) === trim((string) $baru);

            if (!$sama) {
                $berubah[$field] = [$lama, $baru];
            }
        }

        return $berubah;
    }

    /**
     * Apakah penanda lembur SIREP berbeda dengan yang tersimpan.
     *
     * Dipisah dari isi baris: penanda ini ditetapkan PPC mendekati hari-H dan tidak
     * memengaruhi pembagian jadwal (lihat ShiftCapacityCalculator), jadi perubahannya
     * tidak boleh terbaca sebagai "listing direvisi".
     *
     * @param  array<string, mixed>  $attributes
     */
    public function overtimeChanged(ListingStage $stage, array $attributes): bool
    {
        return (bool) $stage->is_overtime !== (bool) ($attributes['is_overtime'] ?? false);
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
