<?php

namespace App\Services;

use App\Models\AssyScheduleCircuit;
use App\Models\AssyScheduleShikake;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get kanban printed count grouped by machine (Circuit - Cutting)
     */
    public function getKanbanPerMachineCutting()
    {
        return AssyScheduleCircuit::query()
            ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
            ->join('master_conveyor', 'master_circuit.conveyor_id', '=', 'master_conveyor.id')
            ->where('assy_schedule_circuit.is_printed', true)
            ->select([
                'master_circuit.machine',
                'master_conveyor.conveyor as conveyor_name',
                DB::raw('COUNT(*) as total_printed'),
                DB::raw('SUM(assy_schedule_circuit.print_count) as total_print_count'),
            ])
            ->groupBy('master_circuit.machine', 'master_conveyor.conveyor')
            ->orderBy('master_circuit.machine')
            ->get();
    }

    /**
     * Get kanban printed count grouped by machine (Shikake)
     */
    public function getKanbanPerMachineShikake()
    {
        return AssyScheduleShikake::query()
            ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
            ->join('master_conveyor', 'master_shikake.conveyor_id', '=', 'master_conveyor.id')
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
            ->get();
    }

    /**
     * Get chart data: kanban printed per machine (combined)
     */
    public function getChartDataPerMachine()
    {
        // Cutting machines
        $cutting = DB::table('assy_schedule_circuit')
            ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
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
        $shikake = DB::table('assy_schedule_shikake')
            ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
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
    }
}
