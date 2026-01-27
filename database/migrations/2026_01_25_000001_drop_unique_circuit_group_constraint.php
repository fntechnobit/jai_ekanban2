<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove unique_circuit_group constraint to allow multiple kanban records
     * per circuit per schedule (needed for multi-issue kanban generation)
     */
    public function up(): void
    {
        Schema::table('assy_schedule_circuit', function (Blueprint $table) {
            $table->dropUnique('unique_circuit_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assy_schedule_circuit', function (Blueprint $table) {
            $table->unique(['assy_schedule_id', 'cct_no', 'cct_code'], 'unique_circuit_group');
        });
    }
};
