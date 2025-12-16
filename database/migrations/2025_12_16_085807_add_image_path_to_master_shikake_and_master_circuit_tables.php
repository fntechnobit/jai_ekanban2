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
        Schema::table('master_shikake', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('joint');
        });

        Schema::table('master_circuit', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('t06');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_shikake', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('master_circuit', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
