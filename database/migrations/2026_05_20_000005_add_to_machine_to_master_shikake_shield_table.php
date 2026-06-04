<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_shikake_shield', function (Blueprint $table) {
            $table->string('to_machine')->nullable()->after('blade');
        });
    }

    public function down(): void
    {
        Schema::table('master_shikake_shield', function (Blueprint $table) {
            $table->dropColumn('to_machine');
        });
    }
};
