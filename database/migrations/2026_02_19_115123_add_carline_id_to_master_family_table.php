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
        Schema::table('master_family', function (Blueprint $table) {
            $table->unsignedBigInteger('carline_id')->nullable()->after('family');
            $table->foreign('carline_id')->references('id')->on('master_carline')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_family', function (Blueprint $table) {
            $table->dropForeign(['carline_id']);
            $table->dropColumn('carline_id');
        });
    }
};
