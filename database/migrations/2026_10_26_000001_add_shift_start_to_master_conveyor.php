<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shift mulai kembali menjadi data master — untuk sementara hanya pada hari satu shift.
 *
 * Hari yang cukup satu shift dijadwalkan di shift ini (1 atau 2). Hari dua shift
 * tidak terpengaruh: tetap mulai dari shift 1, lalu shift 2.
 *
 * Jumlah shift tetap diturunkan dari qty listing terhadap overtime_capacity SIREP
 * (lihat ShiftCapacityCalculator). Nilai ini diisi tangan di form Conveyor Data
 * karena SIREP tidak mengirimnya.
 *
 * Bawaan 1 = perilaku sebelum kolom ini ada, sehingga jadwal tidak berubah sampai
 * nilainya diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->unsignedTinyInteger('shift_start')->default(1)->after('pallet_qty')
                ->comment('Shift tempat hari satu shift dijadwalkan (1/2). Hari dua shift selalu mulai dari shift 1.');
        });
    }

    public function down(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->dropColumn('shift_start');
        });
    }
};
