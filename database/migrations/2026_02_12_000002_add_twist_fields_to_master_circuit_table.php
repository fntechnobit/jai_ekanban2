<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to document twist-related columns added manually to master_circuit.
 * These columns already exist in the database (added via manual SQL).
 * This migration uses hasColumn() checks to be safe for re-running.
 * 
 * Columns documented:
 * - memory_twist: varchar(255) - Memory Twist value
 * - sequence_2: int(11) - Second sequence number for twist operations
 * - barcode_navigasi: varchar(255) - Navigation barcode
 * - barcode_process: varchar(255) - Process barcode
 * - barcode_shikake: varchar(255) - Shikake barcode
 * - to_store: varchar(255) - Store destination
 * 
 * Additionally documents column name corrections:
 * - acc_1b (NOT acc_1) - Accessory 1B
 * - acc_2b (NOT acc_2) - Accessory 2B
 * - No remark_1, remark_2, ta, tb, t04, t05, t06 columns exist
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            if (!Schema::hasColumn('master_circuit', 'memory_twist')) {
                $table->string('memory_twist')->nullable()->after('machine_twist');
            }
            if (!Schema::hasColumn('master_circuit', 'sequence_2')) {
                $table->integer('sequence_2')->nullable()->after('sequence');
            }
            if (!Schema::hasColumn('master_circuit', 'barcode_navigasi')) {
                $table->string('barcode_navigasi')->nullable()->after('barcode_mesin');
            }
            if (!Schema::hasColumn('master_circuit', 'barcode_process')) {
                $table->string('barcode_process')->nullable()->after('barcode_navigasi');
            }
            if (!Schema::hasColumn('master_circuit', 'barcode_shikake')) {
                $table->string('barcode_shikake')->nullable()->after('barcode_process');
            }
            if (!Schema::hasColumn('master_circuit', 'to_store')) {
                $table->string('to_store')->nullable()->after('barcode_shikake');
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $columns = ['memory_twist', 'sequence_2', 'barcode_navigasi', 'barcode_process', 'barcode_shikake', 'to_store'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('master_circuit', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
