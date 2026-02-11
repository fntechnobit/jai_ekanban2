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
            $table->renameColumn('acc_1', 'acc_1b');
            $table->renameColumn('acc_2', 'acc_2b');
            $table->string('memory_twist')->nullable()->after('machine_twist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->renameColumn('acc_1b', 'acc_1');
            $table->renameColumn('acc_2b', 'acc_2');
            $table->dropColumn('memory_twist');
        });
    }
};
