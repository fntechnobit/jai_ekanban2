<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->string('barcode_twist')->nullable()->after('barcode_shikake');
            $table->string('qrcode_drawing')->nullable()->after('barcode_twist');
        });
    }

    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->dropColumn(['barcode_twist', 'qrcode_drawing']);
        });
    }
};
