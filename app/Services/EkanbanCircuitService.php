<?php

namespace App\Services;

use App\Models\AssyScheduleCircuit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EkanbanCircuitService
{
    /**
     * Get circuit data for DataTable - Grouped by cct_no + cct_code
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

        // Clone query for counting before search
        $countQuery = clone $query;
        $totalRecords = DB::table(DB::raw("({$countQuery->toSql()}) as sub"))
            ->mergeBindings($countQuery)
            ->count();
        
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->having(function($q) use ($searchValue) {
                $q->having('master_circuit.cct_no', 'like', "%{$searchValue}%")
                  ->orHaving('master_circuit.cct_code', 'like', "%{$searchValue}%")
                  ->orHaving('master_circuit.machine', 'like', "%{$searchValue}%")
                  ->orHaving('master_circuit.family', 'like', "%{$searchValue}%")
                  ->orHaving(DB::raw('GROUP_CONCAT(DISTINCT master_circuit.barcode_kanban ORDER BY master_circuit.issue SEPARATOR ", ")'), 'like', "%{$searchValue}%");
            });
        }

        $filteredQuery = clone $query;
        $filteredRecords = DB::table(DB::raw("({$filteredQuery->toSql()}) as sub"))
            ->mergeBindings($filteredQuery)
            ->count();

        // Order
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = [
            'assy_schedule_id', 
            'master_circuit.cct_no', 
            'master_circuit.cct_code', 
            'master_circuit.machine', 
            'master_circuit.family', 
            'assy_schedule.qty', 
            'barcodes',
            'issue_count',
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
            // Group composite ID format: assyScheduleId-cctNo-cctCode (URL encoded)
            $groupId = $row->assy_schedule_id . '-' . urlencode($row->cct_no) . '-' . urlencode($row->cct_code);
            
            $result[] = [
                'DT_RowIndex' => $start + $index + 1,
                'assy_schedule_id' => $row->assy_schedule_id,
                'group_id' => $groupId,
                'circuit_ids' => $row->circuit_ids,
                'cct_no' => $row->cct_no,
                'cct_code' => $row->cct_code,
                'conveyor' => $row->conveyor,
                'machine' => $row->machine,
                'family' => $row->family,
                'qty' => $row->qty,
                'barcodes' => $row->barcodes ?? '-',
                'issue_count' => $row->issue_count,
                'date' => Carbon::parse($row->date)->format('d-m-Y'),
                'shift' => 'Shift ' . $row->shift,
                'cutoff' => 'Cut Off ' . $row->cutoff,
                'is_printed' => $row->is_printed,
                'last_printed_at' => $row->last_printed_at,
                'print_count' => $row->print_count ?? 0,
                'actions' => view('schedule.ekanban_circuit.actions', [
                    'row' => $row,
                    'groupId' => $groupId
                ])->render()
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
     * Get circuits for printing - using group IDs (assyScheduleId-cctNo-cctCode)
     */
    public function getCircuitsForPrint(array $groupIds)
    {
        // Parse group IDs (format: "assyScheduleId-cctNo-cctCode")
        $conditions = [];
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 3);
            if (count($parts) === 3) {
                $conditions[] = [
                    'assy_schedule_id' => $parts[0],
                    'cct_no' => urldecode($parts[1]),
                    'cct_code' => urldecode($parts[2])
                ];
            }
        }

        if (empty($conditions)) {
            return collect([]);
        }

        // Fetch ALL circuits matching the group criteria
        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_assy', 'assy_schedule.assy', '=', 'master_assy.assy')
            ->join('master_circuit_assy', 'master_assy.id', '=', 'master_circuit_assy.master_assy_id')
            ->join('master_circuit', 'master_circuit_assy.master_circuit_id', '=', 'master_circuit.id')
            ->leftJoin('listing_stage', 'assy_schedule.listing_id', '=', 'listing_stage.id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->where(function($query) use ($conditions) {
                foreach ($conditions as $condition) {
                    $query->orWhere(function($q) use ($condition) {
                        $q->where('assy_schedule.id', $condition['assy_schedule_id'])
                          ->where('master_circuit.cct_no', $condition['cct_no'])
                          ->where('master_circuit.cct_code', $condition['cct_code']);
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
            ->orderBy('master_circuit.cct_no')
            ->orderBy('master_circuit.issue')
            ->get();
    }

    /**
     * Get base circuit query - Returns grouped data by cct_no + cct_code
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
                     ->on('master_circuit.cct_no', '=', 'assy_schedule_circuit.cct_no')
                     ->on('master_circuit.cct_code', '=', 'assy_schedule_circuit.cct_code');
            })
            ->where('assy_schedule.is_lock', '!=', 0)
            ->select([
                'assy_schedule.id as assy_schedule_id',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.machine',
                'master_circuit.family',
                'master_conveyor.conveyor',
                'assy_schedule.assy',
                'assy_schedule.qty',
                'assy_schedule.schedule as date',
                'assy_schedule.shift',
                'assy_schedule.cutoff',
                // Aggregated fields for grouping
                DB::raw('GROUP_CONCAT(DISTINCT master_circuit.id ORDER BY master_circuit.issue) as circuit_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT master_circuit.barcode_kanban ORDER BY master_circuit.issue SEPARATOR ", ") as barcodes'),
                DB::raw('COUNT(DISTINCT master_circuit.id) as issue_count'),
                // Print status from group table
                'assy_schedule_circuit.is_printed',
                'assy_schedule_circuit.last_printed_at',
                'assy_schedule_circuit.print_count'
            ])
            ->groupBy([
                'assy_schedule.id',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.machine',
                'master_circuit.family',
                'master_conveyor.conveyor',
                'assy_schedule.assy',
                'assy_schedule.qty',
                'assy_schedule.schedule',
                'assy_schedule.shift',
                'assy_schedule.cutoff',
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
     * Mark circuit group as printed
     */
    public function markAsPrinted(array $groupIds, $userId)
    {
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 3);
            if (count($parts) === 3) {
                AssyScheduleCircuit::updateOrCreate(
                    [
                        'assy_schedule_id' => $parts[0],
                        'cct_no' => urldecode($parts[1]),
                        'cct_code' => urldecode($parts[2])
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