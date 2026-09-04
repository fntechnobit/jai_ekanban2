<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master conveyor sepenuhnya milik SIREP.
 *
 * Conveyor tidak lagi dibuat manual: daftarnya ditarik dari API SIREP (tambah +
 * perbarui). Conveyor yang hilang dari API tidak dihapus — statusnya dinonaktifkan
 * agar jadwal dan kanban lamanya tetap dapat ditelusuri, tetapi tidak lagi ikut
 * dijadwalkan maupun diverifikasi.
 *
 *   sirep_conveyor_id : id conveyor di SIREP — kunci pencocokan yang stabil,
 *                       tidak ikut berubah bila namanya diganti PPC.
 *   is_active         : 0 = tidak ada lagi di API SIREP.
 *   master_area_id    : dijadikan nullable. API tidak mengirim area, jadi conveyor
 *                       hasil sinkronisasi masuk tanpa area dan dilengkapi manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->unsignedBigInteger('sirep_conveyor_id')->nullable()->after('sirep_conveyor_code');
            $table->boolean('is_active')->default(true)->after('sirep_conveyor_id');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
        });

        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->unsignedBigInteger('master_area_id')->nullable()->change();
            $table->integer('pallet_qty')->nullable()->default(null)->change();
        });

        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->index('sirep_conveyor_id', 'master_conveyor_sirep_id_index');
            $table->index('is_active', 'master_conveyor_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->dropIndex('master_conveyor_sirep_id_index');
            $table->dropIndex('master_conveyor_is_active_index');
            $table->dropColumn(['sirep_conveyor_id', 'is_active', 'deactivated_at']);
        });
    }
};
