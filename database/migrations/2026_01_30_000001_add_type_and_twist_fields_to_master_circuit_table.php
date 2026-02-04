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
            // Add type field with default 'CUTTING'
            $table->enum('type', ['CUTTING', 'CUTTING_TWIST'])->default('CUTTING')->after('conveyor_id');

            // Add fields from MasterShikakeTwist for CUTTING_TWIST type
            $table->string('machine_twist')->nullable()->after('machine');
            $table->integer('sequence_2')->nullable()->after('sequence');
            $table->string('barcode_navigasi')->nullable()->after('barcode_mesin');
            $table->string('barcode_process')->nullable()->after('barcode_navigasi');
            $table->string('barcode_shikake')->nullable()->after('barcode_process');
            $table->string('to_store')->nullable()->after('barcode_shikake');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'machine_twist',
                'sequence_2',
                'barcode_navigasi',
                'barcode_process',
                'barcode_shikake',
                'to_store',
            ]);
        });
    }
};
