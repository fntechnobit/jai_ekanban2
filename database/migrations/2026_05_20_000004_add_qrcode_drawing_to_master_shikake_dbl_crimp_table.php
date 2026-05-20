<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) {
            $table->string('qrcode_drawing')->nullable()->after('to_machine');
        });
    }

    public function down(): void
    {
        Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) {
            $table->dropColumn('qrcode_drawing');
        });
    }
};
