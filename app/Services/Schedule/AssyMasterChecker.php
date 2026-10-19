<?php

namespace App\Services\Schedule;

use Illuminate\Support\Facades\DB;

/**
 * Apakah sebuah assy punya master circuit/shikake di satu conveyor.
 *
 * Kanban dibentuk saat verifikasi dari master_circuit/master_shikake yang terhubung ke
 * assy itu lewat master_assy DAN berada di conveyor jadwalnya
 * (KanbanGeneratorService::getCircuitsForAssyCodes). Assy tanpa hubungan seperti itu
 * tetap terjadwal dan bisa diverifikasi, tetapi tidak menghasilkan kanban sama sekali —
 * kasus yang wajar untuk assy baru yang diinput lewat Listing Manual sebelum master
 * circuit/shikake-nya diimport. Kelas ini dipakai untuk menandainya lebih awal.
 */
class AssyMasterChecker
{
    /**
     * Assy dari daftar yang tidak punya circuit maupun shikake di conveyor ini.
     *
     * @param  iterable<string>  $assyList
     * @return array<int, string>
     */
    public function missing(int $conveyorId, iterable $assyList): array
    {
        $assyList = collect($assyList)
            ->map(fn ($assy) => trim((string) $assy))
            ->filter()
            ->unique()
            ->values();

        if ($assyList->isEmpty()) {
            return [];
        }

        $mapped = DB::table('master_assy AS m0')
            ->whereIn('m0.assy', $assyList->all())
            ->whereRaw('(' . self::existsSql('m0.assy', '?') . ')', [$conveyorId, $conveyorId])
            ->pluck('m0.assy');

        return $assyList->diff($mapped)->values()->all();
    }

    /**
     * Ekspresi SQL boolean: assy pada $assyExpr punya circuit/shikake (belum dihapus) di
     * conveyor $conveyorIdExpr. Kedua argumen adalah potongan SQL (kolom, subquery, atau
     * '?' dengan binding yang diberikan pemanggil — dua kali untuk $conveyorIdExpr).
     */
    public static function existsSql(string $assyExpr, string $conveyorIdExpr): string
    {
        return "EXISTS (SELECT 1 FROM master_assy ma
                    JOIN master_circuit_assy mca ON mca.master_assy_id = ma.id
                    JOIN master_circuit mc ON mc.id = mca.master_circuit_id AND mc.deleted_at IS NULL
                    WHERE ma.assy = {$assyExpr} AND mc.conveyor_id = {$conveyorIdExpr})
            OR EXISTS (SELECT 1 FROM master_assy ma
                    JOIN master_shikake_assy msa ON msa.master_assy_id = ma.id
                    JOIN master_shikake ms ON ms.id = msa.master_shikake_id AND ms.deleted_at IS NULL
                    WHERE ma.assy = {$assyExpr} AND ms.conveyor_id = {$conveyorIdExpr})";
    }
}
