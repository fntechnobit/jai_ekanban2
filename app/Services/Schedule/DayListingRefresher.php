<?php

namespace App\Services\Schedule;

use App\Models\MasterConveyor;
use App\Services\AssySchedulerService;
use App\Services\Listing\ListingSourceInterface;
use App\Services\ListingSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Pastikan listing dan jadwal pending SATU tanggal × conveyor sesuai SIREP terkini,
 * tepat sebelum operator melihat atau mengunci jadwalnya.
 *
 * Generate otomatis hanya berjalan saat halaman dibuka dan hanya untuk hari ini sampai
 * H+3, sedangkan PPC bisa mengubah listing kapan saja. Kasus B3-ENG 26-10-2026: listing
 * ditarik 21-10 10:29, PPC lalu menaikkan 7YA1B dari seq 4 ke seq 1, dan form verifikasi
 * tetap menampilkan urutan lama sampai generate dijalankan ulang — jadwal seperti itu bisa
 * terkunci dengan urutan yang salah.
 *
 * Satu permintaan ke SIREP (conveyor dan tanggal itu saja, batas waktu pendek):
 *
 *   - listing berbeda     -> staging disinkron, jadwal pending hari itu disusun ulang
 *   - listing sama, tetapi jadwal pending tertinggal dari listing/aturan terbaru
 *                         -> jadwal pending disusun ulang
 *   - hanya penanda lembur berubah -> staging diperbarui, jadwal tidak disentuh
 *   - SIREP tidak bisa dihubungi   -> tidak ada yang diubah; pemanggil memutuskan
 *
 * Penyusunan memakai jalur yang sama dengan generate (AssySchedulerService::susunJadwal),
 * jadi jadwal yang sudah terkunci tidak pernah tersentuh.
 */
class DayListingRefresher
{
    public const FRESH       = 'fresh';
    public const REFRESHED   = 'refreshed';
    public const UNAVAILABLE = 'unavailable';
    public const SKIPPED     = 'skipped';

    public function __construct(
        private readonly ListingSourceInterface $source,
        private readonly ListingSyncService $listingSync,
        private readonly AssySchedulerService $scheduler,
        private readonly DayScheduleBuilder $dayBuilder,
    ) {
    }

    /**
     * @return array{
     *     status: string, message: string, changes: array<int, string>,
     *     notes: array<int, string>, warnings: array<int, string>, rebuilt: bool, checked_at: string
     * }
     *     status   fresh | refreshed | unavailable | skipped
     *     changes  perubahan yang membuat jadwal disusun ulang
     *     notes    perubahan yang tidak memengaruhi jadwal (penanda lembur)
     *     warnings peringatan sinkron (pembatalan yang ditahan pengaman, baris terkunci yang berubah)
     */
    public function refresh(MasterConveyor $conveyor, $date): array
    {
        $tanggal = Carbon::parse($date)->startOfDay();

        $hasil = [
            'status'     => self::FRESH,
            'message'    => '',
            'changes'    => [],
            'notes'      => [],
            'warnings'   => [],
            'rebuilt'    => false,
            'checked_at' => now()->format('d M Y H:i:s'),
        ];

        // Tanggal lampau tidak bisa diverifikasi lagi, dan menyusun ulang sejarahnya hanya
        // mengubah catatan yang sudah tidak dipakai.
        if ($tanggal->lt(Carbon::today())) {
            return ['status' => self::SKIPPED, 'message' => 'Tanggal sudah lewat — listing tidak diperiksa ulang.'] + $hasil;
        }

        $nama = trim((string) $conveyor->conveyor);

        try {
            $fetched = $this->source->quick()->fetch($tanggal->format('Y-m-d'), $tanggal->format('Y-m-d'), [$nama]);
        } catch (\Throwable $e) {
            return $this->unavailable($conveyor, $tanggal, $e->getMessage(), $hasil);
        }

        // Sumber API mengeluarkan conveyor yang gagal diambil dari lingkupnya dan mencatat
        // alasannya di errors — datanya tidak lengkap, jadi tidak boleh dibandingkan.
        if ($fetched->scopeConveyors !== null && !in_array($nama, $fetched->scopeConveyors, true)) {
            return $this->unavailable(
                $conveyor,
                $tanggal,
                implode(' ', $fetched->errors) ?: 'SIREP tidak mengembalikan data conveyor ini.',
                $hasil
            );
        }

        // Bandingkan dulu tanpa menulis: pada keadaan normal listing sudah sama dan tidak ada
        // yang perlu dikunci maupun ditulis.
        $rencana    = $this->listingSync->compare($fetched, $tanggal, $tanggal);
        $isiBerubah = false;

        $hasil['warnings'] = $rencana['warnings'];

        if ($this->listingSync->planChangesStaging($rencana)) {
            // Rencana dihitung ulang di dalam kunci saat ditulis, jadi yang dilaporkan adalah
            // yang benar-benar ditulis — proses lain bisa sudah mendahului.
            $sync = $this->listingSync->applyFetched($fetched, $tanggal, $tanggal);

            if (!$sync['success']) {
                return $this->unavailable($conveyor, $tanggal, $sync['message'], $hasil);
            }

            $hasil['warnings'] = $sync['warnings'];
            $hasil['changes']  = $sync['changes'];
            $hasil['notes']    = $sync['notes'];
            $isiBerubah        = $sync['changes'] !== [];
        }

        $listings = $this->dayBuilder->listingQuery()
            ->where('conveyor', $conveyor->conveyor)
            ->whereDate('listing_date_time', $tanggal->format('Y-m-d'))
            ->get();

        $tertinggal = !$isiBerubah && $this->dayBuilder->pendingIsStale($conveyor, $tanggal, $listings);

        if ($isiBerubah || $tertinggal) {
            $susun = $this->scheduler->susunJadwal($tanggal, $tanggal->copy()->endOfDay(), $conveyor->id);

            if (!$susun['success']) {
                Log::error('Penyusunan ulang satu hari gagal', [
                    'conveyor' => $nama,
                    'date'     => $tanggal->format('Y-m-d'),
                    'error'    => $susun['error'],
                ]);

                throw new \RuntimeException('Gagal menyusun ulang jadwal ' . $nama . ' '
                    . $tanggal->format('d-m-Y') . ': ' . $susun['error']);
            }

            $hasil['rebuilt'] = true;

            foreach ($susun['belum_terjadwal'] as $sisa) {
                $hasil['warnings'][] = "Sebagian listing belum terjadwal karena seluruh shift hari itu sudah diverifikasi: {$sisa}.";
            }

            if ($tertinggal) {
                $hasil['changes'][] = 'Jadwal pending tertinggal dari listing terbaru (mis. listing disinkron tanpa '
                    . 'generate, item dipindah ke tanggal lain, atau aturan pembagian shift berubah).';
            }
        }

        if ($hasil['rebuilt']) {
            $hasil['status']  = self::REFRESHED;
            $hasil['message'] = $isiBerubah
                ? 'Listing SIREP berubah sejak sinkron terakhir. Jadwal pending hari ini sudah disusun ulang.'
                : 'Jadwal pending hari ini sudah disusun ulang mengikuti listing terbaru.';
        } else {
            $hasil['message'] = 'Listing sesuai SIREP.';
        }

        Log::info('Pemeriksaan listing satu hari', [
            'conveyor' => $nama,
            'date'     => $tanggal->format('Y-m-d'),
            'status'   => $hasil['status'],
            'changes'  => $hasil['changes'],
            'notes'    => $hasil['notes'],
            'warnings' => $hasil['warnings'],
        ]);

        return $hasil;
    }

    private function unavailable(MasterConveyor $conveyor, Carbon $tanggal, string $alasan, array $hasil): array
    {
        Log::warning('Pemeriksaan listing satu hari: SIREP tidak dapat dipastikan', [
            'conveyor' => $conveyor->conveyor,
            'date'     => $tanggal->format('Y-m-d'),
            'reason'   => $alasan,
        ]);

        return [
            'status'  => self::UNAVAILABLE,
            'message' => 'SIREP tidak dapat dihubungi, data listing memakai hasil sinkron terakhir. (' . $alasan . ')',
        ] + $hasil;
    }
}
