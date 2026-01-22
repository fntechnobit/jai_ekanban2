<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: issue, barcode_kanban, release_date fields are being moved
     * to assy_schedule_shikake and assy_schedule_circuit tables where they will be
     * generated dynamically during kanban generation.
     * released_note remains in master tables as it's a static field.
     */
    public function up(): void
    {
        Schema::table('master_shikake', function (Blueprint $table) {
            $table->dropColumn(['issue', 'barcode_kanban']);
        });

        Schema::table('master_circuit', function (Blueprint $table) {
            $table->dropColumn(['issue', 'barcode_kanban', 'release_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_shikake', function (Blueprint $table) {
            $table->string('issue', 100)->nullable()->after('qty');
            $table->string('barcode_kanban', 100)->nullable()->after('issue');
        });

        Schema::table('master_circuit', function (Blueprint $table) {
            $table->string('issue', 100)->nullable()->after('qty');
            $table->string('barcode_kanban', 100)->nullable()->after('sequence');
            $table->date('release_date')->nullable()->after('barcode_kanban');
        });
    }
};
