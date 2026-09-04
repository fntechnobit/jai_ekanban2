<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kapasitas & shift berhenti menjadi master data yang diisi tangan.
 *
 * Sejak jai_ekanban2 memakai API SIREP sebagai acuan:
 *   - `capacity` hanya boleh ditulis oleh `sirep:sync-conveyor` (kolom SIREP),
 *     karena itu dijadikan nullable — NULL berarti "belum pernah disinkron",
 *     kondisi yang harus terlihat jelas, bukan disamarkan angka tebakan.
 *   - `shift_qty` / `shift_start` dihapus. Jumlah shift kini diturunkan per
 *     tanggal dari qty listing dan flag is_overtime SIREP, bukan dari nilai
 *     statis per conveyor. Batas atasnya diatur config `sirep.capacity.max_shift`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->dropColumn(['shift_qty', 'shift_start']);
        });

        // Dipisah: sebagian driver menolak drop kolom dan ubah tipe dalam satu statement.
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->integer('capacity')->nullable()->comment('Diisi sirep:sync-conveyor. NULL = belum sinkron.')->change();
        });
    }

    public function down(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->integer('shift_start')->default(1);
            $table->integer('shift_qty')->default(1);
        });

        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->integer('capacity')->nullable(false)->default(0)->change();
        });
    }
};
