<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_machine', function (Blueprint $table) {
            // Area pemilik machine. Conveyor yang boleh dipilih dibatasi ke area ini.
            $table->foreignId('master_area_id')
                ->nullable()
                ->after('machine')
                ->constrained('master_area')
                ->nullOnDelete();
        });

        // Backfill: machine lama mengikuti area dari conveyor yang sudah terhubung.
        $rows = DB::table('master_machine_conveyor as mmc')
            ->join('master_conveyor as mc', 'mc.id', '=', 'mmc.conveyor_id')
            ->whereNull('mc.deleted_at')
            ->orderBy('mmc.machine_id')
            ->orderBy('mmc.id')
            ->get(['mmc.machine_id', 'mc.master_area_id']);

        foreach ($rows->groupBy('machine_id') as $machineId => $machineRows) {
            DB::table('master_machine')
                ->where('id', $machineId)
                ->update(['master_area_id' => $machineRows->first()->master_area_id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_machine', function (Blueprint $table) {
            $table->dropForeign(['master_area_id']);
            $table->dropColumn('master_area_id');
        });
    }
};
