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
            $table->dropColumn('kanban');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->string('kanban')->nullable()->after('barcode_kanban');
        });
    }
};
