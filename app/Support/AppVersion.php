<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Penanda versi kode yang sedang berjalan.
 *
 * Gunanya satu: memastikan server benar-benar sudah memakai kode terbaru.
 * Beberapa kali error yang sudah diperbaiki masih muncul di produksi karena
 * `git pull` belum dijalankan atau OPcache masih memegang berkas lama — tanpa
 * penanda di layar, keduanya tidak dapat dibedakan dari "perbaikannya salah".
 *
 * Sumber angkanya, berurutan:
 *
 *   1. berkas VERSION di akar proyek  — untuk deploy yang tidak membawa .git
 *   2. .git/HEAD                      — deploy lewat `git pull` (yang dipakai sekarang)
 *   3. config('app.version')          — cadangan terakhir
 *
 * Tidak ada pemanggilan `git` lewat shell: pada XAMPP produksi `exec()` sering
 * dimatikan dan biner git belum tentu ada di PATH. Cukup membaca berkas .git
 * langsung, yang selalu ikut terbarui setiap kali pull.
 */
class AppVersion
{
    /** @var array<string, mixed>|null Dibaca sekali per request. */
    private static ?array $memo = null;

    /**
     * @return array{ref:string, short:string, branch:?string, deployed_at:?Carbon, source:string, opcache_manual:bool}
     */
    public static function info(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        return self::$memo = self::baca();
    }

    /** Label pendek untuk navbar, mis. "80613d5". */
    public static function short(): string
    {
        return self::info()['short'];
    }

    /** Keterangan lengkap untuk tooltip. */
    public static function keterangan(): string
    {
        $i = self::info();

        $bagian = ['Versi kode ' . $i['ref']];

        if ($i['branch']) {
            $bagian[] = 'branch ' . $i['branch'];
        }

        if ($i['deployed_at']) {
            $bagian[] = 'diperbarui ' . $i['deployed_at']->format('d M Y H:i');
        }

        if ($i['opcache_manual']) {
            $bagian[] = 'OPcache tidak memvalidasi timestamp — restart Apache setelah pull '
                      . 'agar kode baru benar-benar dipakai';
        }

        return implode(' · ', $bagian);
    }

    /**
     * Apakah OPcache dapat menahan kode lama tanpa batas waktu.
     *
     * Angka versi di footer dibaca dari berkas .git, yang selalu segar begitu
     * `git pull` selesai. Kode PHP-nya tidak: dengan `opcache.validate_timestamps=0`
     * OPcache tidak pernah memeriksa ulang berkas sumber, sehingga versi di layar
     * bisa terlihat baru padahal yang berjalan masih kode lama sampai Apache
     * di-restart. Kalau validasi timestamp menyala, basinya paling lama selama
     * `revalidate_freq` dan tidak perlu diperingatkan.
     *
     * Sengaja memakai pembacaan ini saja, bukan opcache_get_status(true) — yang
     * terakhir mengembalikan seluruh daftar skrip dan terlalu mahal untuk dipanggil
     * pada setiap render halaman.
     */
    private static function opcacheManual(): bool
    {
        if (!function_exists('opcache_get_status') || !filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return !filter_var(ini_get('opcache.validate_timestamps'), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array{ref:string, short:string, branch:?string, deployed_at:?Carbon, source:string, opcache_manual:bool} */
    private static function baca(): array
    {
        $akar = base_path();

        // 1. VERSION — ditulis manual atau oleh skrip deploy.
        $berkasVersi = $akar . DIRECTORY_SEPARATOR . 'VERSION';

        if (is_readable($berkasVersi)) {
            $isi = trim((string) @file_get_contents($berkasVersi));

            if ($isi !== '') {
                return self::hasil($isi, null, self::waktu($berkasVersi), 'file');
            }
        }

        // 2. .git/HEAD — pada repo hasil clone biasa berisi "ref: refs/heads/main".
        $head = $akar . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'HEAD';

        if (is_readable($head)) {
            $isi    = trim((string) @file_get_contents($head));
            $branch = null;
            $sha    = null;
            $waktu  = self::waktu($head);

            if (str_starts_with($isi, 'ref:')) {
                $ref    = trim(substr($isi, 4));
                $branch = str_replace('refs/heads/', '', $ref);
                $berkas = $akar . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR
                        . str_replace('/', DIRECTORY_SEPARATOR, $ref);

                if (is_readable($berkas)) {
                    $sha   = trim((string) @file_get_contents($berkas));
                    $waktu = self::waktu($berkas);
                } else {
                    // Ref yang sudah dipaket (git gc) tidak lagi punya berkas sendiri.
                    $sha = self::dariPackedRefs($akar, $ref);
                }
            } else {
                // detached HEAD: isinya langsung sha.
                $sha = $isi;
            }

            if ($sha) {
                return self::hasil($sha, $branch, $waktu, 'git');
            }
        }

        // 3. Cadangan terakhir.
        return self::hasil((string) config('app.version', 'dev'), null, null, 'config');
    }

    private static function dariPackedRefs(string $akar, string $ref): ?string
    {
        $packed = $akar . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'packed-refs';

        if (!is_readable($packed)) {
            return null;
        }

        foreach (preg_split('/\R/', (string) @file_get_contents($packed)) ?: [] as $baris) {
            if ($baris === '' || $baris[0] === '#' || $baris[0] === '^') {
                continue;
            }

            [$sha, $nama] = array_pad(preg_split('/\s+/', trim($baris), 2) ?: [], 2, null);

            if ($nama === $ref) {
                return $sha;
            }
        }

        return null;
    }

    private static function waktu(string $berkas): ?Carbon
    {
        $ts = @filemtime($berkas);

        // Zona waktu dipasang eksplisit: filemtime adalah epoch UTC, dan tanpa ini
        // jamnya tampil geser 7 jam pada proses yang belum memuat config aplikasi
        // (mis. dijalankan lewat CLI).
        return $ts ? Carbon::createFromTimestamp($ts, config('app.timezone', 'Asia/Jakarta')) : null;
    }

    /** @return array{ref:string, short:string, branch:?string, deployed_at:?Carbon, source:string, opcache_manual:bool} */
    private static function hasil(string $ref, ?string $branch, ?Carbon $waktu, string $source): array
    {
        $ref = trim($ref);

        // Sha git dipendekkan; nomor versi manual (mis. "1.4.2") dibiarkan utuh.
        $short = preg_match('/^[0-9a-f]{40}$/i', $ref) ? substr($ref, 0, 7) : $ref;

        return [
            'ref'            => $ref,
            'short'          => $short,
            'branch'         => $branch,
            'deployed_at'    => $waktu,
            'source'         => $source,
            'opcache_manual' => self::opcacheManual(),
        ];
    }
}
