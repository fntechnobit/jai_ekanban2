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
        // Add shikake_code to master_circuit table
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->string('shikake_code', 100)->nullable()->after('cct_code');
        });

        // Add qrcode_shikake to assy_schedule_circuit table
        Schema::table('assy_schedule_circuit', function (Blueprint $table) {
            $table->string('qrcode_shikake', 100)->nullable()->after('barcode_kanban');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->dropColumn('shikake_code');
        });

        Schema::table('assy_schedule_circuit', function (Blueprint $table) {
            $table->dropColumn('qrcode_shikake');
        });
    }
};
