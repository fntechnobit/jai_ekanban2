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
        Schema::table('master_circuit', function (Blueprint $table) {
            // Add separate machine and sequence columns
            $table->string('machine')->nullable()->after('issue');
            $table->string('sequence')->nullable()->after('machine');
            
            // Drop the combined machine_sequence column
            $table->dropColumn('machine_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            // Add back the combined machine_sequence column
            $table->string('machine_sequence')->nullable()->after('issue');
            
            // Drop the separate columns
            $table->dropColumn(['machine', 'sequence']);
        });
    }
};
