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
        // Drop released_date column from master_shikake_twist table
        Schema::table('master_shikake_twist', function (Blueprint $table) {
            $table->dropColumn('released_date');
        });

        // Drop released_date column from master_shikake_bonder table
        Schema::table('master_shikake_bonder', function (Blueprint $table) {
            $table->dropColumn('released_date');
        });

        // Drop released_date column from master_shikake_joint table
        Schema::table('master_shikake_joint', function (Blueprint $table) {
            $table->dropColumn('released_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add released_date column to master_shikake_twist table
        Schema::table('master_shikake_twist', function (Blueprint $table) {
            $table->date('released_date')->nullable()->after('family');
        });

        // Re-add released_date column to master_shikake_bonder table
        Schema::table('master_shikake_bonder', function (Blueprint $table) {
            $table->date('released_date')->nullable()->after('master_shikake_id');
        });

        // Re-add released_date column to master_shikake_joint table
        Schema::table('master_shikake_joint', function (Blueprint $table) {
            $table->date('released_date')->nullable()->after('master_shikake_id');
        });
    }
};
