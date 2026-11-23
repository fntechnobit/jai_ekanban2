<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan kanban printed per mesin untuk Dashboard.
 *
 * Dulu menghitung SELURUH riwayat assy_schedule_circuit (±650 ribu baris printed,
 * terus bertambah) setiap kali dashboard dibuka: ±4 detik CPU MySQL per query,
 * ±9 detik per buka halaman. Dashboard adalah halaman pertama sesudah login,
 * jadi banyak PC sekaligus = CPU server penuh. Query cache MariaDB tidak
 * menolong karena setiap print mengubah tabelnya.
 *
 * Sekarang:
 *   - hanya jadwal PERIOD_DAYS hari terakhir (lewat index assy_schedule.schedule)
 *   - hasil disimpan di cache CACHE_SECONDS, dipakai bersama semua user
 */
class DashboardService
{
    /** Rentang tanggal jadwal yang diringkas (termasuk hari ini). */
    public const PERIOD_DAYS = 7;

    /** Lama hasil ringkasan disimpan sebelum dihitung ulang. */
    public const CACHE_SECONDS = 300;

    /** Tanggal jadwal paling awal yang ikut dihitung. */
    private function periodStart(): string
    {
        return now()->subDays(self::PERIOD_DAYS - 1)->toDateString();
    }

    private function remember(string $key, \Closure $callback)
    {
        return Cache::remember(
            'dashboard:' . $key . ':' . $this->periodStart(),
            self::CACHE_SECONDS,
            $callback
        );
    }

    /**
     * Get kanban printed count grouped by machine (Circuit - Cutting)
     */
    public function getKanbanPerMachineCutting()
    {
        return $this->remember('cutting', fn () => DB::table('assy_schedule')
            ->join('assy_schedule_circuit', 'assy_schedule_circuit.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
            ->join('master_conveyor', 'master_circuit.conveyor_id', '=', 'master_conveyor.id')
            ->where('assy_schedule.schedule', '>=', $this->periodStart())
            ->where('assy_schedule_circuit.is_printed', true)
            ->select([
                'master_circuit.machine',
                'master_conveyor.conveyor as conveyor_name',
                DB::raw('COUNT(*) as total_printed'),
                DB::raw('SUM(assy_schedule_circuit.print_count) as total_print_count'),
            ])
            ->groupBy('master_circuit.machine', 'master_conveyor.conveyor')
            ->orderBy('master_circuit.machine')
            ->get());
    }

    /**
     * Get kanban printed count grouped by machine (Shikake)
     */
    public function getKanbanPerMachineShikake()
    {
        return $this->remember('shikake', fn () => DB::table('assy_schedule')
            ->join('assy_schedule_shikake', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
            ->join('master_conveyor', 'master_shikake.conveyor_id', '=', 'master_conveyor.id')
            ->where('assy_schedule.schedule', '>=', $this->periodStart())
            ->where('assy_schedule_shikake.is_printed', true)
            ->select([
                'master_shikake.machine',
                'master_shikake.process',
                'master_conveyor.conveyor as conveyor_name',
                DB::raw('COUNT(*) as total_printed'),
                DB::raw('SUM(assy_schedule_shikake.print_count) as total_print_count'),
            ])
            ->groupBy('master_shikake.machine', 'master_shikake.process', 'master_conveyor.conveyor')
            ->orderBy('master_shikake.machine')
            ->get());
    }

    /**
     * Get chart data: kanban printed per machine (combined)
     */
    public function getChartDataPerMachine()
    {
        return $this->remember('chart', function () {
            $start = $this->periodStart();

            // Cutting machines
            $cutting = DB::table('assy_schedule')
                ->join('assy_schedule_circuit', 'assy_schedule_circuit.assy_schedule_id', '=', 'assy_schedule.id')
                ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
                ->where('assy_schedule.schedule', '>=', $start)
                ->where('assy_schedule_circuit.is_printed', true)
                ->whereNotNull('master_circuit.machine')
                ->where('master_circuit.machine', '!=', '')
                ->select([
                    'master_circuit.machine',
                    DB::raw("'Cutting' as type"),
                    DB::raw('COUNT(*) as total'),
                ])
                ->groupBy('master_circuit.machine');

            // Shikake machines
            $shikake = DB::table('assy_schedule')
                ->join('assy_schedule_shikake', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
                ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
                ->where('assy_schedule.schedule', '>=', $start)
                ->where('assy_schedule_shikake.is_printed', true)
                ->whereNotNull('master_shikake.machine')
                ->where('master_shikake.machine', '!=', '')
                ->select([
                    'master_shikake.machine',
                    DB::raw("'Shikake' as type"),
                    DB::raw('COUNT(*) as total'),
                ])
                ->groupBy('master_shikake.machine');

            // Union both
            $results = $cutting->unionAll($shikake)->get();

            // Pivot: group by machine, split into cutting & shikake
            $machines = [];
            foreach ($results as $row) {
                $machine = $row->machine;
                if (!isset($machines[$machine])) {
                    $machines[$machine] = ['cutting' => 0, 'shikake' => 0];
                }
                if ($row->type === 'Cutting') {
                    $machines[$machine]['cutting'] += $row->total;
                } else {
                    $machines[$machine]['shikake'] += $row->total;
                }
            }

            // Sort by total desc
            uasort($machines, function ($a, $b) {
                return ($b['cutting'] + $b['shikake']) - ($a['cutting'] + $a['shikake']);
            });

            // Take top 20 for chart readability
            $machines = array_slice($machines, 0, 20, true);

            return [
                'labels' => array_keys($machines),
                'cutting' => array_column(array_values($machines), 'cutting'),
                'shikake' => array_column(array_values($machines), 'shikake'),
            ];
        });
    }
}
