<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom pendukung integrasi API SIREP (khusus jai_ekanban2).
 *
 * master_conveyor
 *   - sirep_conveyor_code : dipakai bila nama conveyor di SIREP berbeda dengan
 *                           nama master. Kosong = pakai kolom `conveyor`.
 *   - overtime_capacity   : nilai overtime_capacity dari API SIREP, disimpan
 *                           sebagai pembanding terhadap perhitungan CO5 sendiri.
 *   - capacity_synced_at  : kapan kapasitas terakhir ditarik dari SIREP.
 *
 * listing_stage
 *   - is_overtime : penanda dari SIREP bahwa hari itu ada CO5 / kapasitas over.
 *                   Dipakai sebagai pemeriksa silang saat generate jadwal.
 *   - source      : asal baris ('api' atau 'db') untuk penelusuran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->string('sirep_conveyor_code', 50)->nullable()->after('conveyor');
            $table->integer('overtime_capacity')->nullable()->after('capacity');
            $table->timestamp('capacity_synced_at')->nullable()->after('overtime_capacity');
        });

        Schema::table('listing_stage', function (Blueprint $table) {
            $table->boolean('is_overtime')->default(false)->after('mode');
            $table->string('source', 10)->default('db')->after('id_listing');
        });
    }

    public function down(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->dropColumn(['sirep_conveyor_code', 'overtime_capacity', 'capacity_synced_at']);
        });

        Schema::table('listing_stage', function (Blueprint $table) {
            $table->dropColumn(['is_overtime', 'source']);
        });
    }
};
