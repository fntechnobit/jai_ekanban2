<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add released_note to master_circuit table
        if (!Schema::hasColumn('master_circuit', 'released_note')) {
            Schema::table('master_circuit', function (Blueprint $table) {
                $table->text('released_note')->nullable()->after('sequence');
            });
        }

        // Add released_note to master_shikake table
        if (!Schema::hasColumn('master_shikake', 'released_note')) {
            Schema::table('master_shikake', function (Blueprint $table) {
                $table->text('released_note')->nullable()->after('sequence');
            });
        }

        // Remove released_date from master_shikake table (moved to assy_schedule_shikake)
        if (Schema::hasColumn('master_shikake', 'released_date')) {
            Schema::table('master_shikake', function (Blueprint $table) {
                $table->dropColumn('released_date');
            });
        }

        // Remove released_date from master_circuit table if exists
        if (Schema::hasColumn('master_circuit', 'released_date')) {
            Schema::table('master_circuit', function (Blueprint $table) {
                $table->dropColumn('released_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add released_date to master_shikake
        if (!Schema::hasColumn('master_shikake', 'released_date')) {
            Schema::table('master_shikake', function (Blueprint $table) {
                $table->date('released_date')->nullable()->after('family');
            });
        }

        // Re-add released_date to master_circuit
        if (!Schema::hasColumn('master_circuit', 'released_date')) {
            Schema::table('master_circuit', function (Blueprint $table) {
                $table->date('released_date')->nullable()->after('sequence');
            });
        }

        // Drop released_note from master_circuit
        if (Schema::hasColumn('master_circuit', 'released_note')) {
            Schema::table('master_circuit', function (Blueprint $table) {
                $table->dropColumn('released_note');
            });
        }

        // Drop released_note from master_shikake
        if (Schema::hasColumn('master_shikake', 'released_note')) {
            Schema::table('master_shikake', function (Blueprint $table) {
                $table->dropColumn('released_note');
            });
        }
    }
};
