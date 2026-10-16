<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listing manual: baris listing yang diinput langsung di e-Kanban, bukan dari SIREP.
 *
 * Disimpan di listing_stage dengan source = 'manual' supaya ikut seluruh alur yang
 * sudah ada (generate, verifikasi, kanban) tanpa jalur kedua. Sinkronisasi SIREP
 * tidak pernah menyentuh baris manual.
 *
 * listing_stage
 *   - remark                 : alasan/keterangan input manual.
 *   - created_by, updated_by : siapa yang menginput dan terakhir mengubah.
 *   - uq_listing_stage + `source`
 *       Listing manual boleh memakai seq yang sama dengan listing SIREP pada tanggal
 *       dan conveyor yang sama. Tanpa ini, baris SIREP yang datang belakangan dengan
 *       kunci yang kebetulan sama akan menggagalkan seluruh sinkronisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_stage', function (Blueprint $table) {
            $table->string('remark')->nullable()->after('snpa');
            $table->unsignedBigInteger('created_by')->nullable()->after('synced_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        Schema::table('listing_stage', function (Blueprint $table) {
            $table->dropUnique('uq_listing_stage');
            $table->unique(
                ['listing_date_time', 'conveyor', 'assycode', 'shift', 'seq', 'source'],
                'uq_listing_stage'
            );
        });
    }

    public function down(): void
    {
        // Gagal bila listing manual masih berbagi kunci dengan listing SIREP;
        // hapus dulu listing manual yang bentrok sebelum rollback.
        Schema::table('listing_stage', function (Blueprint $table) {
            $table->dropUnique('uq_listing_stage');
            $table->unique(['listing_date_time', 'conveyor', 'assycode', 'shift', 'seq'], 'uq_listing_stage');
        });

        Schema::table('listing_stage', function (Blueprint $table) {
            $table->dropColumn(['remark', 'created_by', 'updated_by']);
        });
    }
};
