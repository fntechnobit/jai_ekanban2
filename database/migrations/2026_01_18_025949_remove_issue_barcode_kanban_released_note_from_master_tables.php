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
            $table->dropColumn(['issue', 'barcode_kanban', 'released_note']);
        });

        Schema::table('master_circuit', function (Blueprint $table) {
            $table->dropColumn(['issue', 'barcode_kanban', 'released_date', 'released_note']);
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
            $table->text('released_note')->nullable()->after('released_date');
        });

        Schema::table('master_circuit', function (Blueprint $table) {
            $table->string('issue', 100)->nullable()->after('qty');
            $table->string('barcode_kanban', 100)->nullable()->after('sequence');
            $table->date('released_date')->nullable()->after('barcode_kanban');
            $table->text('released_note')->nullable()->after('released_date');
        });
    }
};
