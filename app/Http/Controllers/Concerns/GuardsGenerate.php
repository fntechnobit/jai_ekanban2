<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaman agar sinkron+generate tidak berjalan berlipat.
 *
 * Setiap halaman jadwal memicu generate otomatis begitu dibuka, dan tombol
 * Generate memicu yang kedua. Keduanya menggarap tabel yang sama, sehingga dua
 * proses berat saling menunggu kunci basis data dan lamanya berlipat — persis
 * gejala "generate sangat lama lalu timeout". Ruang kuncinya sengaja dipakai
 * bersama semua halaman: Dashboard, Assy Scheduler dan Schedule Verification
 * mengerjakan pekerjaan yang sama persis untuk rentang yang sama.
 *
 * Dua lapis:
 *   THROTTLE  panggilan otomatis (`auto=1`) dilewati bila rentang yang sama baru
 *             saja selesai ATAU baru saja gagal. Penekanan tombol manual tidak
 *             pernah dilewati.
 *   LOCK      hanya satu proses per rentang yang boleh berjalan pada satu waktu.
 *
 * Yang dilewati dibalas 200 dengan `skipped: true` — bukan kegagalan, sehingga
 * layar tidak menampilkannya sebagai error pengambilan data.
 */
trait GuardsGenerate
{
    /** Kunci per rentang, dipakai bersama seluruh halaman. */
    protected function generateScope(Request $request): string
    {
        return sprintf(
            'assy-generate:%s:%s:%s',
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('conveyor_id') ?: 'all'
        );
    }

    /**
     * Hasil generate untuk rentang ini yang baru saja selesai, bila panggilannya
     * otomatis. null berarti proses boleh jalan.
     *
     * @return array{at:string, generated:int}|null
     */
    protected function generateBaruSelesai(Request $request, string $scope): ?array
    {
        $throttle = (int) config('sirep.generate.auto_throttle_seconds', 300);

        if (!$request->boolean('auto') || $throttle <= 0) {
            return null;
        }

        $recent = Cache::get($scope . ':done');

        return is_array($recent) ? $recent : null;
    }

    /** Catat keberhasilan supaya panggilan otomatis berikutnya bisa dilewati. */
    protected function catatGenerateSelesai(string $scope, int $generated): void
    {
        $throttle = (int) config('sirep.generate.auto_throttle_seconds', 300);

        // Berhasil = catatan gagal sebelumnya tidak berlaku lagi.
        Cache::forget($scope . ':failed');

        if ($throttle > 0) {
            Cache::put($scope . ':done', [
                'at'        => now()->format('H:i:s'),
                'generated' => $generated,
            ], $throttle);
        }
    }

    /**
     * Kegagalan generate untuk rentang ini yang baru saja terjadi, bila panggilannya
     * otomatis. Tanpa ini, saat PPC/SIREP bermasalah SETIAP halaman yang dibuka
     * (dashboard sesudah login, assy scheduler, verifikasi) menjalankan ulang sinkron
     * + generate penuh — hanya keberhasilan yang dicatat, jadi throttle tidak pernah
     * berlaku dan beban server menumpuk. Tombol manual tetap selalu dijalankan.
     *
     * @return array{at:string, step_failed:?string, message:string}|null
     */
    protected function generateBaruGagal(Request $request, string $scope): ?array
    {
        $throttle = (int) config('sirep.generate.auto_throttle_seconds', 300);

        if (!$request->boolean('auto') || $throttle <= 0) {
            return null;
        }

        $failed = Cache::get($scope . ':failed');

        return is_array($failed) ? $failed : null;
    }

    /** Catat kegagalan supaya panggilan otomatis berikutnya tidak langsung mengulang. */
    protected function catatGenerateGagal(string $scope, ?string $stepFailed, string $message): void
    {
        $throttle = (int) config('sirep.generate.auto_throttle_seconds', 300);

        if ($throttle > 0) {
            Cache::put($scope . ':failed', [
                'at'          => now()->format('H:i:s'),
                'step_failed' => $stepFailed,
                'message'     => $message,
            ], $throttle);
        }
    }

    /**
     * Balasan untuk panggilan otomatis yang tidak dijalankan karena percobaan
     * sebelumnya baru saja gagal. Pesan gagalnya tetap ditampilkan (bukan "dilewati")
     * supaya operator tahu datanya belum tersinkron dan bisa menekan tombol manual.
     */
    protected function failedRecentlyResponse(array $failed)
    {
        $throttleMinutes = max(1, (int) ceil(config('sirep.generate.auto_throttle_seconds', 300) / 60));

        return response()->json([
            'success'     => false,
            'skipped'     => false,
            'step_failed' => $failed['step_failed'] ?? 'unknown',
            'message'     => ($failed['message'] ?? 'Sinkron & generate gagal.')
                . ' (Percobaan otomatis terakhir gagal pukul ' . ($failed['at'] ?? '-')
                . '; dicoba otomatis lagi setelah ' . $throttleMinutes . ' menit,'
                . ' atau tekan tombol Sinkron & Generate.)',
            'data'        => ['generated' => 0, 'sync_detail' => null],
        ]);
    }

    protected function ambilGenerateLock(string $scope)
    {
        return Cache::lock($scope . ':lock', (int) config('sirep.generate.lock_seconds', 600));
    }

    /**
     * Balasan untuk permintaan yang sengaja tidak dijalankan (sedang berjalan
     * atau hasilnya masih segar). Dikirim sebagai 200 supaya layar tidak
     * menampilkannya sebagai kegagalan mengambil data dari PPC.
     */
    protected function skippedResponse(string $message, int $generated = 0)
    {
        return response()->json([
            'success'     => true,
            'skipped'     => true,
            'step_failed' => null,
            'message'     => $message,
            'data'        => ['generated' => $generated, 'sync_detail' => null],
        ]);
    }
}
