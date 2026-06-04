<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate rows before adding the unique constraint.
        // Uniqueness only matters when all three key columns are filled
        // (MySQL treats NULL key columns as distinct, so they never collide).
        // Keep one row per (conveyor_id, cct_code, to_store): prefer the
        // active (non soft-deleted) row, then the lowest id.
        \DB::statement("
            DELETE c1 FROM master_circuit c1
            INNER JOIN master_circuit c2
                ON  c1.conveyor_id = c2.conveyor_id
                AND c1.cct_code    = c2.cct_code
                AND c1.to_store    = c2.to_store
                AND (
                        (c2.deleted_at IS NULL AND c1.deleted_at IS NOT NULL)
                        OR (
                              ((c2.deleted_at IS NULL) = (c1.deleted_at IS NULL))
                              AND c2.id < c1.id
                           )
                    )
            WHERE c1.conveyor_id IS NOT NULL
              AND c1.cct_code    IS NOT NULL
              AND c1.to_store    IS NOT NULL
        ");

        Schema::table('master_circuit', function (Blueprint $table) {
            // One conveyor may hold several cct_code, and the same cct_code may
            // repeat within a conveyor as long as to_store differs.
            $table->unique(['conveyor_id', 'cct_code', 'to_store'], 'uq_master_circuit_cct');
        });
    }

    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->dropUnique('uq_master_circuit_cct');
        });
    }
};
