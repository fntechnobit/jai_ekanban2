<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EkanbanCircuitService
{
    /**
     * Get circuit data for DataTable
     */
    public function getCircuitDataForTable(Request $request)
    {
        // Require machine filter only
        if (!$request->filled('machine')) {
            return [
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ];
        }

        $query = $this->getBaseCircuitQuery();

        // Apply filters
        $this->applyFilters($query, $request);

        // DataTable processing
        $totalRecords = $query->count();
        
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function($q) use ($searchValue) {
                $q->where('master_conveyor.conveyor', 'like', "%{$searchValue}%")
                  ->orWhere('assy_schedule.assy', 'like', "%{$searchValue}%")
                  ->orWhere('master_circuit.cct_no', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        // Order
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['id', 'conveyor', 'machine', 'dates', 'shift', 'assy', 'listing', 'circuit_name'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $data = $query->skip($start)->take($length)->get();

        $result = [];
        foreach ($data as $index => $row) {
            $result[] = [
                'DT_RowIndex' => $start + $index + 1,
                'conveyor' => $row->conveyor,
                'machine' => $row->machine,
                'dates' => Carbon::parse($row->dates)->format('d-m-Y'),
                'shift' => 'Shift ' . $row->shift,
                'assy' => $row->assy,
                'listing' => $row->listing,
                'circuit_name' => $row->circuit_name,
                'actions' => view('schedule.ekanban_circuit.actions', ['row' => $row])->render()
            ];
        }

        return [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $result
        ];
    }

    /**
     * Get circuits for printing
     */
    public function getCircuitsForPrint(array $ids)
    {
        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_assy', 'assy_schedule.assy', '=', 'master_assy.assy')
            ->join('master_circuit_assy', 'master_assy.id', '=', 'master_circuit_assy.master_assy_id')
            ->join('master_circuit', 'master_circuit_assy.master_circuit_id', '=', 'master_circuit.id')
            ->whereIn('assy_schedule.id', $ids)
            ->select([
                'assy_schedule.*',
                'master_conveyor.conveyor',
                'master_circuit.id as circuit_id',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.family',
                'master_circuit.cust_no',
                'master_circuit.kind',
                'master_circuit.size',
                'master_circuit.col',
                'master_circuit.cl',
                'master_circuit.machine',
                'master_circuit.sequence',
                'master_circuit.barcode_kanban',
                'master_circuit.released_date',
                'master_circuit.released_note',
                'master_circuit.terminal_1',
                'master_circuit.note_1',
                'master_circuit.gold_1',
                'master_circuit.strip_1',
                'master_circuit.acc_1',
                'master_circuit.acc_1a',
                'master_circuit.tube_1',
                'master_circuit.mark_1',
                'master_circuit.terminal_2',
                'master_circuit.note_2',
                'master_circuit.gold_2',
                'master_circuit.strip_2',
                'master_circuit.acc_2',
                'master_circuit.acc_2a',
                'master_circuit.tube_2',
                'master_circuit.mark_2',
                'master_circuit.ta',
                'master_circuit.tb'
            ])
            ->get();
    }

    /**
     * Get base circuit query
     */
    private function getBaseCircuitQuery()
    {
        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_assy', 'assy_schedule.assy', '=', 'master_assy.assy')
            ->join('master_circuit_assy', 'master_assy.id', '=', 'master_circuit_assy.master_assy_id')
            ->join('master_circuit', 'master_circuit_assy.master_circuit_id', '=', 'master_circuit.id')
            ->select([
                DB::raw('MIN(assy_schedule.id) as id'),
                'master_conveyor.conveyor',
                'master_circuit.machine',
                DB::raw('MAX(assy_schedule.schedule) as dates'),
                'assy_schedule.shift',
                'assy_schedule.assy',
                DB::raw('SUM(assy_schedule.qty) as listing'),
                DB::raw('MAX(master_circuit.cct_no) as circuit_name')
            ])
            ->groupBy('master_conveyor.conveyor', 'master_circuit.machine', 'assy_schedule.shift', 'assy_schedule.assy')
            ->orderBy('master_conveyor.conveyor', 'ASC')
            ->orderBy('master_circuit.machine', 'ASC')
            ->orderBy('assy_schedule.shift', 'ASC')
            ->orderBy('assy_schedule.assy', 'ASC');
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        // Machine is required
        $query->where('master_circuit.machine', $request->machine);

        // Conveyor is optional
        if ($request->filled('conveyor_id')) {
            $query->where('assy_schedule.conveyor_id', $request->conveyor_id);
        }

        if ($request->filled('area_id')) {
            $query->where('assy_schedule.area_id', $request->area_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('assy_schedule.schedule', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('assy_schedule.schedule', '<=', $request->end_date);
        }

        if ($request->filled('shift')) {
            $query->where('assy_schedule.shift', $request->shift);
        }
    }
}