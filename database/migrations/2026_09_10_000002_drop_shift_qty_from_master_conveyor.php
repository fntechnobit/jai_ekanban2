<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jumlah shift berhenti menjadi data master.
 *
 * Sejak kapasitas conveyor sepenuhnya berasal dari API SIREP, jumlah shift dapat
 * diturunkan langsung dari data yang sama:
 *
 *   qty listing harian > overtime_capacity  ->  2 shift
 *   selain itu                              ->  1 shift
 *
 * Tidak ada informasi yang hilang: seluruh line secara fisik memungkinkan dua
 * shift, sehingga `shift_qty` tidak lagi menyimpan batasan apa pun.
 *
 * Migrasi turun mengembalikan kolomnya dengan nilai 1 — nilai lama tidak dipulihkan
 * karena sudah tidak menjadi acuan sejak perubahan ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->dropColumn('shift_qty');
        });
    }

    public function down(): void
    {
        Schema::table('master_conveyor', function (Blueprint $table) {
            $table->unsignedTinyInteger('shift_qty')->default(1)->after('is_active');
        });
    }
};
