<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Aturan urutan print cut off: CO1 & CO2 harus selesai diprint sebelum CO3-CO5 terbuka.
 * Aturan ini diatur per area (master_area.cutoff_print_rule, default ON) dan dipakai
 * print cutting (circuit) maupun shikake.
 */
class CutoffPrintRule
{
    /** Kondisi SQL "area conveyor ini memakai aturan"; conveyor tanpa area dianggap ON. */
    public const SQL_RULE_ON = 'COALESCE((SELECT ma.cutoff_print_rule FROM master_area ma WHERE ma.id = master_conveyor.master_area_id), 1) = 1';

    public static function isOnForArea($areaId): bool
    {
        if (empty($areaId)) {
            return true;
        }

        $rule = DB::table('master_area')->where('id', $areaId)->value('cutoff_print_rule');

        return $rule === null || (int) $rule === 1;
    }

    /**
     * Dari daftar assy_schedule_id, kembalikan [id => true] untuk jadwal yang areanya
     * mematikan aturan - kanban jadwal itu boleh diprint di cut off berapa pun.
     */
    public static function scheduleIdsWithRuleOff(array $assyScheduleIds): array
    {
        if (empty($assyScheduleIds)) {
            return [];
        }

        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_area', 'master_conveyor.master_area_id', '=', 'master_area.id')
            ->whereIn('assy_schedule.id', $assyScheduleIds)
            ->where('master_area.cutoff_print_rule', 0)
            ->pluck('assy_schedule.id')
            ->flip()
            ->map(fn () => true)
            ->all();
    }
}
