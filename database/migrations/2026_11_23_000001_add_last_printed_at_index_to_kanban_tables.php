<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index last_printed_at untuk halaman History print.
 *
 * History memfilter rentang tanggal print (last_printed_at). Tanpa index, MySQL
 * memindai seluruh assy_schedule_circuit (±717 ribu baris) tiga kali per request
 * (count total, count filter, data) - ±6 detik per buka History circuit.
 */
return new class extends Migration
{
    private const INDEXES = [
        'assy_schedule_circuit' => 'idx_circuit_last_printed_at',
        'assy_schedule_shikake' => 'idx_shikake_last_printed_at',
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $index) {
            if (!Schema::hasIndex($table, $index)) {
                Schema::table($table, function (Blueprint $t) use ($index) {
                    $t->index('last_printed_at', $index);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $index) {
            if (Schema::hasIndex($table, $index)) {
                Schema::table($table, function (Blueprint $t) use ($index) {
                    $t->dropIndex($index);
                });
            }
        }
    }
};
