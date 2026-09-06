<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lengkapi snapshot verifikasi dengan total demand SIREP hari itu.
 *
 * verified_capacity/is_overtime/listing_synced_at/listing_source (migrasi
 * sebelumnya) sudah membekukan asal-usul data, tapi layar Detail juga
 * menghitung ulang listing_demand LANGSUNG dari listing_stage setiap kali
 * dibuka — nilai itu bisa berubah bila listing_stage dikoreksi/di-resync
 * setelah verifikasi. Kolom ini membekukan angka demand itu juga, supaya
 * form Detail jadwal yang sudah verified benar-benar menampilkan histori,
 * bukan angka SIREP terkini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->integer('verified_listing_demand')->nullable()->after('verified_listing_source');
        });
    }

    public function down(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->dropColumn('verified_listing_demand');
        });
    }
};
