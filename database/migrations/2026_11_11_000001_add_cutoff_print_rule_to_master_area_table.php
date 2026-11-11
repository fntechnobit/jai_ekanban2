<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aturan urutan print cut off (CO1 & CO2 harus selesai diprint sebelum CO3-CO5)
     * kini bisa dimatikan per area. Default ON supaya area yang ada tetap berperilaku
     * seperti sebelumnya sampai dimatikan dari Master Area.
     */
    public function up(): void
    {
        Schema::table('master_area', function (Blueprint $table) {
            $table->boolean('cutoff_print_rule')->default(true)->after('area');
        });
    }

    public function down(): void
    {
        Schema::table('master_area', function (Blueprint $table) {
            $table->dropColumn('cutoff_print_rule');
        });
    }
};
