<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration documents columns that were added manually to the database:
     * - type: enum to differentiate between CUTTING and CUTTING_TWIST processes
     * - machine_twist: additional machine field for twist operations
     * 
     * These columns already exist in the database but were not previously tracked
     * in migration files. This migration uses hasColumn checks to safely add them
     * if they don't exist (for fresh installations) or skip if they already exist.
     */
    public function up(): void
    {
        // Add type column if it doesn't exist
        if (!Schema::hasColumn('master_circuit', 'type')) {
            Schema::table('master_circuit', function (Blueprint $table) {
                $table->enum('type', ['CUTTING', 'CUTTING_TWIST'])
                      ->default('CUTTING')
                      ->after('conveyor_id')
                      ->comment('Type of circuit process: CUTTING or CUTTING_TWIST');
            });
        }
        
        // Add machine_twist column if it doesn't exist
        if (!Schema::hasColumn('master_circuit', 'machine_twist')) {
            Schema::table('master_circuit', function (Blueprint $table) {
                $table->string('machine_twist', 255)
                      ->nullable()
                      ->after('machine')
                      ->comment('Machine used for twist operations');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            if (Schema::hasColumn('master_circuit', 'machine_twist')) {
                $table->dropColumn('machine_twist');
            }
            
            if (Schema::hasColumn('master_circuit', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
