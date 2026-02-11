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
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->integer('plt')->nullable()->after('seq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->dropColumn('plt');
        });
    }
};
