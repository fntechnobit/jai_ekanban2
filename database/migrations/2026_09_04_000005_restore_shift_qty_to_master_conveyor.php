<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Jumlah shift kembali menjadi data master.
 *
 * Keputusan konsep: shift TIDAK dihitung dari volume listing, melainkan ditetapkan
 * per conveyor. Alokasi selalu mulai dari shift 1; shift 2 hanya dipakai bila
 * conveyor itu memang dua shift.
 *
 * Pengisian nilai awal:
 *   1. dari database v1 (koneksi `mysql_reference`) — sumber yang mengikat
 *   2. bila tidak ada padanannya di v1, dipakai nilai terakhir yang tercatat di
 *      dump v2 3 September. Enam conveyor berikut hanya ada di v2, dan
 *      mengembalikannya ke 1 akan menjejalkan seluruh listing ke CO5 shift 1.
 *   3. sisanya 1 (paling konservatif)
 *
 * `shift_start` TIDAK dikembalikan — alokasi selalu mulai dari shift 1.
 */
return new class extends Migration
{
    /** Cadangan dari dump v2 untuk conveyor yang tidak ada di v1. */
    private const CADANGAN_V2 = [
        'C1'  => 1,
        'C7'  => 1,
        'C4'  => 2,
        'AB5' => 2,
        'C6'  => 2,
        'C9'  => 2,
    ];

    public function up(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->unsignedTinyInteger('shift_qty')->default(1)->after('is_active')
                ->comment('Jumlah shift yang berjalan. Data master, bukan hasil hitungan.');
        });

        $dariV1 = $this->shiftQtyDariV1();
        $laporan = ['v1' => 0, 'cadangan' => 0, 'bawaan' => 0];

        foreach (DB::table('master_conveyor')->get(['id', 'conveyor']) as $c) {
            $nama = trim((string) $c->conveyor);

            if (isset($dariV1[$nama])) {
                $nilai = $dariV1[$nama];
                $laporan['v1']++;
            } elseif (isset(self::CADANGAN_V2[$nama])) {
                $nilai = self::CADANGAN_V2[$nama];
                $laporan['cadangan']++;
            } else {
                $nilai = 1;
                $laporan['bawaan']++;
            }

            DB::table('master_conveyor')->where('id', $c->id)->update(['shift_qty' => $nilai]);
        }

        Log::info('shift_qty dipulihkan ke master_conveyor', $laporan);
    }

    /**
     * Baca shift_qty dari database v1. Bila koneksi pembanding tidak tersedia,
     * migrasi tetap jalan dan seluruh conveyor memakai cadangan/bawaan — lebih
     * baik daripada gagal di tengah dan meninggalkan kolom tanpa isi.
     *
     * @return array<string, int>
     */
    private function shiftQtyDariV1(): array
    {
        try {
            return DB::connection('mysql_reference')
                ->table('master_conveyor')
                ->whereNull('deleted_at')
                ->pluck('shift_qty', 'conveyor')
                ->map(fn ($v) => max(1, (int) $v))
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Tidak dapat membaca shift_qty dari v1, memakai nilai cadangan', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function down(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->dropColumn('shift_qty');
        });
    }
};
