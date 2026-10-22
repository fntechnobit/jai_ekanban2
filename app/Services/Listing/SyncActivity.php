<?php

namespace App\Services\Listing;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Catatan kapan sinkron SIREP dan generate jadwal TERAKHIR BERJALAN, untuk badge di header.
 *
 * Badge lama membaca MAX(listing_stage.synced_at) dan MAX(assy_schedule.created_at). Keduanya
 * bukan waktu proses berjalan:
 *   - synced_at hanya bergeser bila listing berubah atau baru masuk — sinkron yang tidak
 *     menemukan perubahan (kasus paling umum) tidak menggesernya;
 *   - created_at ikut bergeser saat Verify dan unverify, karena keduanya membuat ulang baris.
 * Akibatnya "Sinkron" tampak tertinggal jauh dari "Generate" padahal keduanya berjalan
 * dalam satu proses.
 *
 * Yang dicatat hanya sinkron SELURUH conveyor (generate tanpa filter dan menu Sinkron
 * Listing) — pemeriksaan satu conveyor × tanggal dari layar verifikasi bukan "sinkron
 * terakhir" bagi conveyor lain. Disimpan di cache aplikasi (store database); bila cache
 * dikosongkan, badge kembali ke perkiraan dari data sampai proses berikutnya berjalan.
 */
class SyncActivity
{
    private const SYNC_OK     = 'sirep-activity:sync-ok';
    private const SYNC_FAILED = 'sirep-activity:sync-failed';
    private const GENERATE    = 'sirep-activity:generate';

    /**
     * Catat hasil satu sinkron seluruh conveyor.
     *
     * @param  array<string, mixed>  $result  Hasil ListingSyncService::syncListingData()
     */
    public function recordSync(array $result, $startDate, $endDate): void
    {
        $entry = [
            'at'    => now()->toDateTimeString(),
            'range' => [Carbon::parse($startDate)->format('Y-m-d'), Carbon::parse($endDate)->format('Y-m-d')],
        ];

        if (!($result['success'] ?? false)) {
            $this->put(self::SYNC_FAILED, $entry + ['message' => (string) ($result['message'] ?? 'Sinkron gagal')]);

            return;
        }

        $this->put(self::SYNC_OK, $entry + [
            'checked'  => (int) ($result['total_records'] ?? 0),
            'inserted' => (int) ($result['inserted'] ?? 0),
            'updated'  => (int) ($result['updated'] ?? 0),
            'deleted'  => (int) ($result['deleted'] ?? 0),
            'failed'   => count($result['errors'] ?? []),
        ]);
    }

    /**
     * Catat satu generate yang selesai.
     */
    public function recordGenerate($startDate, $endDate, ?string $conveyor, int $generated): void
    {
        $this->put(self::GENERATE, [
            'at'        => now()->toDateTimeString(),
            'range'     => [Carbon::parse($startDate)->format('Y-m-d'), Carbon::parse($endDate)->format('Y-m-d')],
            'conveyor'  => $conveyor,
            'generated' => $generated,
        ]);
    }

    /**
     * Ringkasan untuk badge header.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $ok       = Cache::get(self::SYNC_OK);
        $failed   = Cache::get(self::SYNC_FAILED);
        $generate = Cache::get(self::GENERATE);

        // Kapan isi listing SIREP terakhir berubah — keterangan, bukan waktu sinkron.
        $changedAt = ListingStage::fromSirep()->max('synced_at');

        $sync = [
            'last_sync'          => null,
            'last_sync_detail'   => null,
            'last_sync_recorded' => false,
            'last_sync_failed'   => null,
            'listing_changed_at' => $changedAt ? $this->format($changedAt) : null,
        ];

        if (is_array($ok)) {
            $sync['last_sync']          = $this->format($ok['at']);
            $sync['last_sync_recorded'] = true;
            $sync['last_sync_detail']   = sprintf(
                'Rentang %s · %d listing dicek: %d baru, %d berubah, %d dihapus%s',
                $this->range($ok['range']),
                $ok['checked'],
                $ok['inserted'],
                $ok['updated'],
                $ok['deleted'],
                $ok['failed'] > 0 ? " · {$ok['failed']} permintaan gagal" : ''
            );
            $sync['last_sync_changes'] = $ok['inserted'] + $ok['updated'] + $ok['deleted'];
        } elseif ($changedAt) {
            // Belum ada catatan (baru diperbarui / cache dikosongkan): perkiraan dari data.
            $sync['last_sync'] = $this->format($changedAt);
        }

        // Kegagalan hanya relevan bila lebih baru dari sinkron yang berhasil.
        if (is_array($failed) && (!is_array($ok) || $failed['at'] > $ok['at'])) {
            $sync['last_sync_failed'] = [
                'at'      => $this->format($failed['at']),
                'message' => $failed['message'],
            ];
        }

        $gen = [
            'last_generate'          => null,
            'last_generate_detail'   => null,
            'last_generate_recorded' => false,
        ];

        if (is_array($generate)) {
            $gen['last_generate']          = $this->format($generate['at']);
            $gen['last_generate_recorded'] = true;
            $gen['last_generate_detail']   = sprintf(
                'Rentang %s · %s · %d jadwal disusun',
                $this->range($generate['range']),
                $generate['conveyor'] ? 'conveyor ' . $generate['conveyor'] : 'semua conveyor',
                $generate['generated']
            );
        } elseif ($fallback = AssySchedule::where('is_lock', 0)->max('created_at')) {
            // Perkiraan: jadwal pending terakhir dibuat. Jadwal terverifikasi dikecualikan
            // karena verify ikut membuat ulang barisnya.
            $gen['last_generate'] = $this->format($fallback);
        }

        return $sync + $gen;
    }

    private function put(string $key, array $value): void
    {
        try {
            Cache::forever($key, $value);
        } catch (\Throwable $e) {
            // Catatan badge tidak boleh menggagalkan sinkron maupun generate.
            Log::warning('Catatan aktivitas sinkron gagal disimpan', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    private function format($value): string
    {
        return Carbon::parse($value)->format('d M Y H:i:s');
    }

    /** @param array{0: string, 1: string} $range */
    private function range(array $range): string
    {
        [$dari, $sampai] = $range;

        return $dari === $sampai
            ? Carbon::parse($dari)->format('d-m-Y')
            : Carbon::parse($dari)->format('d-m') . ' s/d ' . Carbon::parse($sampai)->format('d-m-Y');
    }
}
