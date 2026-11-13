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
        Schema::table('master_family', function (Blueprint $table) {
            // Area pemilik family (sama seperti master_machine). Family tidak lagi
            // bergantung pada carline: carline hanya info tambahan yang opsional.
            $table->foreignId('master_area_id')
                ->nullable()
                ->after('family')
                ->constrained('master_area')
                ->nullOnDelete();
        });

        // Backfill 1: family lama mengikuti area dari carline-nya.
        DB::table('master_family')
            ->whereNotNull('carline_id')
            ->orderBy('id')
            ->get(['id', 'carline_id'])
            ->each(function ($family) {
                $areaId = DB::table('master_carline')->where('id', $family->carline_id)->value('area_id');
                if ($areaId) {
                    DB::table('master_family')->where('id', $family->id)->update(['master_area_id' => $areaId]);
                }
            });

        // Backfill 2: family yang sudah dipakai di data Circuit/Shikake tetapi
        // belum terdaftar di master (per area dari conveyor-nya).
        $used = DB::table('master_circuit as t')
            ->join('master_conveyor as c', 'c.id', '=', 't.conveyor_id')
            ->whereNull('t.deleted_at')
            ->whereNotNull('t.family')
            ->where('t.family', '<>', '')
            ->whereNotNull('c.master_area_id')
            ->select('t.family', 'c.master_area_id')
            ->union(
                DB::table('master_shikake as t')
                    ->join('master_conveyor as c', 'c.id', '=', 't.conveyor_id')
                    ->whereNull('t.deleted_at')
                    ->whereNotNull('t.family')
                    ->where('t.family', '<>', '')
                    ->whereNotNull('c.master_area_id')
                    ->select('t.family', 'c.master_area_id')
            )
            ->get();

        $now = now();
        foreach ($used as $row) {
            $family = trim($row->family);
            if ($family === '') {
                continue;
            }

            $exists = DB::table('master_family')
                ->where('family', $family)
                ->where('master_area_id', $row->master_area_id)
                ->whereNull('deleted_at')
                ->exists();

            if (!$exists) {
                DB::table('master_family')->insert([
                    'family' => $family,
                    'master_area_id' => $row->master_area_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_family', function (Blueprint $table) {
            $table->dropForeign(['master_area_id']);
            $table->dropColumn('master_area_id');
        });
    }
};
