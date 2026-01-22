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
        Schema::table('assy_schedule_circuit', function (Blueprint $table) {
            $table->string('released_note', 255)->nullable()->after('release_date');
        });

        Schema::table('assy_schedule_shikake', function (Blueprint $table) {
            $table->string('released_note', 255)->nullable()->after('release_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assy_schedule_circuit', function (Blueprint $table) {
            $table->dropColumn('released_note');
        });

        Schema::table('assy_schedule_shikake', function (Blueprint $table) {
            $table->dropColumn('released_note');
        });
    }
};
