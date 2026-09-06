<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot kapasitas/OT/waktu-tarik-API SIREP pada saat jadwal diverifikasi.
 *
 * Tanpa ini, kolom Cap/OT/API Time di layar verifikasi selalu menampilkan nilai
 * SIREP TERKINI dari master_conveyor/listing_stage — padahal baris yang sudah
 * lock dibangun dari nilai SIREP yang berlaku SAAT verifikasi. Bila kapasitas
 * atau penanda lembur berubah setelahnya (resync SIREP), tampilan jadi
 * menyesatkan: seolah jadwal yang sudah final tidak konsisten dengan aturan
 * kapasitas saat ini, padahal itu memang snapshot dari waktu yang berbeda.
 *
 * Null pada baris yang sudah lock SEBELUM migrasi ini berarti "tidak ada
 * snapshot tercatat" — layar tetap fallback ke nilai SIREP terkini untuk baris
 * lama tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->integer('verified_capacity')->nullable()->after('verified_by');
            $table->boolean('verified_is_overtime')->nullable()->after('verified_capacity');
            $table->timestamp('verified_listing_synced_at')->nullable()->after('verified_is_overtime');
            $table->string('verified_listing_source', 10)->nullable()->after('verified_listing_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->dropColumn([
                'verified_capacity',
                'verified_is_overtime',
                'verified_listing_synced_at',
                'verified_listing_source',
            ]);
        });
    }
};
