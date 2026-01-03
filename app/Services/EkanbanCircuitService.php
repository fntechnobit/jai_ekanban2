<?php

namespace App\Services;

use App\Models\AssyScheduleCircuit;
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
                $q->where('master_circuit.cct_no', 'like', "%{$searchValue}%")
                  ->orWhere('master_circuit.cct_code', 'like', "%{$searchValue}%")
                  ->orWhere('master_circuit.machine', 'like', "%{$searchValue}%")
                  ->orWhere('master_circuit.family', 'like', "%{$searchValue}%")
                  ->orWhere('master_circuit.barcode_kanban', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        // Order
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = [
            'assy_schedule_id', 
            'master_circuit.cct_no', 
            'master_circuit.cct_code', 
            'master_circuit.machine', 
            'master_circuit.family', 
            'master_circuit.kind', 
            'master_circuit.size', 
            'master_circuit.col', 
            'master_circuit.barcode_kanban', 
            'assy_schedule.schedule', 
            'assy_schedule.shift', 
            'assy_schedule.cutoff'
        ];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        // Handle "Show All" case (length = -1)
        if ($length == -1) {
            $data = $query->get();
        } else {
            $data = $query->skip($start)->take($length)->get();
        }

        $result = [];
        foreach ($data as $index => $row) {
            // Create composite ID for tracking individual circuits
            $compositeId = $row->assy_schedule_id . '-' . $row->circuit_id;
            
            $result[] = [
                'DT_RowIndex' => $start + $index + 1,
                'assy_schedule_id' => $row->assy_schedule_id,
                'circuit_id' => $row->circuit_id,
                'composite_id' => $compositeId,
                'cct_no' => $row->cct_no,
                'cct_code' => $row->cct_code,
                'machine' => $row->machine,
                'family' => $row->family,
                'kind' => $row->kind,
                'size' => $row->size,
                'col' => $row->col,
                'barcode_kanban' => $row->barcode_kanban ?? '-',
                'date' => Carbon::parse($row->date)->format('d-m-Y'),
                'shift' => 'Shift ' . $row->shift,
                'cutoff' => 'Cut Off ' . $row->cutoff,
                'is_printed' => $row->is_printed,
                'last_printed_at' => $row->last_printed_at,
                'print_count' => $row->print_count ?? 0,
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
     * Get circuits for printing - using composite IDs
     */
    public function getCircuitsForPrint(array $ids)
    {
        // Parse composite IDs (format: "assyScheduleId-circuitId")
        $conditions = [];
        foreach ($ids as $id) {
            if (strpos($id, '-') !== false) {
                [$assyScheduleId, $circuitId] = explode('-', $id);
                $conditions[] = ['assy_schedule_id' => $assyScheduleId, 'circuit_id' => $circuitId];
            }
        }

        if (empty($conditions)) {
            return collect([]);
        }

        // Build query with OR conditions for each composite ID
        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_assy', 'assy_schedule.assy', '=', 'master_assy.assy')
            ->join('master_circuit_assy', 'master_assy.id', '=', 'master_circuit_assy.master_assy_id')
            ->join('master_circuit', 'master_circuit_assy.master_circuit_id', '=', 'master_circuit.id')
            ->leftJoin('listing_stage', 'assy_schedule.listing_id', '=', 'listing_stage.id')
            ->where(function($query) use ($conditions) {
                foreach ($conditions as $condition) {
                    $query->orWhere(function($q) use ($condition) {
                        $q->where('assy_schedule.id', $condition['assy_schedule_id'])
                          ->where('master_circuit.id', $condition['circuit_id']);
                    });
                }
            })
            ->select([
                'assy_schedule.*',
                'assy_schedule.id as assy_schedule_id',
                'master_conveyor.conveyor',
                'listing_stage.carline',
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
                'master_circuit.tb',
                'master_circuit.qty',
                'master_circuit.issue',
                'master_circuit.address',
                'master_circuit.t01',
                'master_circuit.t02',
                'master_circuit.t03',
                'master_circuit.t04',
                'master_circuit.t05',
                'master_circuit.t06',
                'master_circuit.remark_1',
                'master_circuit.remark_2',
                'master_circuit.barcode_mesin'
            ])
            ->get();
    }

    /**
     * Get base circuit query - Returns one row per circuit (not grouped)
     */
    private function getBaseCircuitQuery()
    {
        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_assy', 'assy_schedule.assy', '=', 'master_assy.assy')
            ->join('master_circuit_assy', 'master_assy.id', '=', 'master_circuit_assy.master_assy_id')
            ->join('master_circuit', 'master_circuit_assy.master_circuit_id', '=', 'master_circuit.id')
            ->leftJoin('assy_schedule_circuit', function($join) {
                $join->on('assy_schedule.id', '=', 'assy_schedule_circuit.assy_schedule_id')
                     ->on('master_circuit.id', '=', 'assy_schedule_circuit.circuit_id');
            })
            ->select([
                'assy_schedule.id as assy_schedule_id',
                'master_circuit.id as circuit_id',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.machine',
                'master_circuit.family',
                'master_circuit.kind',
                'master_circuit.size',
                'master_circuit.col',
                'master_circuit.barcode_kanban',
                'assy_schedule.schedule as date',
                'assy_schedule.shift',
                'assy_schedule.cutoff',
                'master_conveyor.conveyor',
                'assy_schedule.assy',
                'assy_schedule.qty',
                'master_circuit.issue',
                'assy_schedule_circuit.is_printed',
                'assy_schedule_circuit.last_printed_at',
                'assy_schedule_circuit.print_count'
            ])
            ->orderBy('master_circuit.cct_no', 'ASC')
            ->orderBy('assy_schedule.schedule', 'ASC')
            ->orderBy('assy_schedule.shift', 'ASC')
            ->orderBy('assy_schedule.cutoff', 'ASC');
    }

    /**
     * Mark circuits as printed
     */
    public function markAsPrinted(array $compositeIds, $userId)
    {
        foreach ($compositeIds as $compositeId) {
            if (strpos($compositeId, '-') !== false) {
                [$assyScheduleId, $circuitId] = explode('-', $compositeId);
                
                AssyScheduleCircuit::updateOrCreate(
                    [
                        'assy_schedule_id' => $assyScheduleId,
                        'circuit_id' => $circuitId
                    ],
                    [
                        'is_printed' => true,
                        'last_printed_at' => now(),
                        'last_printed_by' => $userId,
                        'print_count' => DB::raw('print_count + 1')
                    ]
                );
            }
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        // Machine is required
        $query->where('master_circuit.machine', $request->machine);

        // Cut off filter
        if ($request->filled('cutoff')) {
            $query->where('assy_schedule.cutoff', $request->cutoff);
        }

        // Area filter (through conveyor's master_area_id)
        if ($request->filled('area_id')) {
            $query->where('master_conveyor.master_area_id', $request->area_id);
        }

        // Single date filter
        if ($request->filled('date')) {
            $query->whereDate('assy_schedule.schedule', $request->date);
        }

        if ($request->filled('shift')) {
            $query->where('assy_schedule.shift', $request->shift);
        }

        // Print status filter
        if ($request->filled('print_status')) {
            switch ($request->print_status) {
                case 'printed':
                    $query->where('assy_schedule_circuit.is_printed', 1);
                    break;
                case 'not_printed':
                    $query->where(function($q) {
                        $q->whereNull('assy_schedule_circuit.is_printed')
                          ->orWhere('assy_schedule_circuit.is_printed', 0);
                    });
                    break;
                case 'all':
                default:
                    // No filter
                    break;
            }
        }
    }
}