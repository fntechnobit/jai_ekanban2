<?php

namespace App\Services;

use App\Models\AssyScheduleCircuit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EkanbanCircuitService
{
    /**
     * Get circuit data for DataTable - Grouped by assy_schedule_id + master_circuit_id
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
            $escapedSearch = '%' . addcslashes($searchValue, '%_') . '%';
            $query->havingRaw("(
                cct_no LIKE ?
                OR cct_code LIKE ?
                OR machine LIKE ?
                OR family LIKE ?
                OR barcodes LIKE ?
            )", [$escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch]);
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
            'cct_no', 
            'cct_code', 
            'machine', 
            'family', 
            'master_circuit.qty', 
            'barcodes',
            'issue_count',
            'date', 
            'shift', 
            'cutoff'
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
            // New Group ID format: assyScheduleId-masterCircuitId
            $groupId = $row->assy_schedule_id . '-' . $row->master_circuit_id;
            
            $result[] = [
                'DT_RowIndex' => $start + $index + 1,
                'assy_schedule_id' => $row->assy_schedule_id,
                'group_id' => $groupId,
                'master_circuit_id' => $row->master_circuit_id,
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
     * Get circuits for printing - using group IDs (assyScheduleId-masterCircuitId)
     */
    public function getCircuitsForPrint(array $groupIds)
    {
        // Parse group IDs (format: "assyScheduleId-masterCircuitId")
        $conditions = [];
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 2);
            if (count($parts) === 2) {
                $conditions[] = [
                    'assy_schedule_id' => $parts[0],
                    'master_circuit_id' => $parts[1]
                ];
            }
        }

        if (empty($conditions)) {
            return collect([]);
        }

        // Fetch ALL kanban records matching the group criteria
        return DB::table('assy_schedule_circuit')
            ->join('assy_schedule', 'assy_schedule_circuit.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
            ->leftJoin('listing_stage', 'assy_schedule.listing_id', '=', 'listing_stage.id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->where(function($query) use ($conditions) {
                foreach ($conditions as $condition) {
                    $query->orWhere(function($q) use ($condition) {
                        $q->where('assy_schedule_circuit.assy_schedule_id', $condition['assy_schedule_id'])
                          ->where('assy_schedule_circuit.master_circuit_id', $condition['master_circuit_id']);
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
                'master_circuit.address',
                'master_circuit.t01',
                'master_circuit.t02',
                'master_circuit.t03',
                'master_circuit.t04',
                'master_circuit.t05',
                'master_circuit.t06',
                'master_circuit.remark_1',
                'master_circuit.remark_2',
                'master_circuit.barcode_mesin',
                'master_circuit.released_note',
                // Kanban fields from assy_schedule_circuit
                'assy_schedule_circuit.issue',
                'assy_schedule_circuit.barcode_kanban',
                'assy_schedule_circuit.release_date',
                'assy_schedule_circuit.qty_listing',
                'assy_schedule_circuit.qty_kanban',
                'assy_schedule_circuit.cutoff as kanban_cutoff'
            ])
            ->orderBy('master_circuit.cct_no')
            ->orderBy('assy_schedule_circuit.issue')
            ->get();
    }

    /**
     * Get base circuit query - Main table is assy_schedule_circuit, grouped by assy_schedule_id + master_circuit_id
     */
    private function getBaseCircuitQuery()
    {
        return DB::table('assy_schedule_circuit')
            ->join('assy_schedule', 'assy_schedule_circuit.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->select([
                'assy_schedule_circuit.assy_schedule_id',
                'assy_schedule_circuit.master_circuit_id',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.machine',
                'master_circuit.family',
                'master_circuit.qty',
                'master_conveyor.conveyor',
                'assy_schedule.assy',
                'assy_schedule.schedule as date',
                'assy_schedule.shift',
                DB::raw('MAX(assy_schedule_circuit.cutoff) as cutoff'),
                // Aggregated fields for grouping
                DB::raw('GROUP_CONCAT(assy_schedule_circuit.barcode_kanban ORDER BY assy_schedule_circuit.issue SEPARATOR ", ") as barcodes'),
                DB::raw('COUNT(*) as issue_count'),
                // Print status - MIN means all must be printed for group to be "printed"
                DB::raw('MIN(assy_schedule_circuit.is_printed) as is_printed'),
                DB::raw('MAX(assy_schedule_circuit.last_printed_at) as last_printed_at'),
                DB::raw('MAX(assy_schedule_circuit.print_count) as print_count')
            ])
            ->groupBy([
                'assy_schedule_circuit.assy_schedule_id',
                'assy_schedule_circuit.master_circuit_id',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.machine',
                'master_circuit.family',
                'master_circuit.qty',
                'master_conveyor.conveyor',
                'assy_schedule.assy',
                'assy_schedule.schedule',
                'assy_schedule.shift'
            ])
            ->orderBy('master_circuit.cct_no', 'ASC')
            ->orderBy('assy_schedule.schedule', 'ASC')
            ->orderBy('assy_schedule.shift', 'ASC');
    }

    /**
     * Mark circuit group as printed - updates ALL rows in the group
     */
    public function markAsPrinted(array $groupIds, $userId)
    {
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 2);
            if (count($parts) === 2) {
                $assyScheduleId = $parts[0];
                $masterCircuitId = $parts[1];
                
                // Update ALL kanban rows in this group
                AssyScheduleCircuit::where('assy_schedule_id', $assyScheduleId)
                    ->where('master_circuit_id', $masterCircuitId)
                    ->update([
                        'is_printed' => true,
                        'last_printed_at' => now(),
                        'last_printed_by' => $userId,
                        'print_count' => DB::raw('print_count + 1')
                    ]);
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
            $query->where('assy_schedule_circuit.cutoff', $request->cutoff);
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

        // Print status filter - use HAVING with MIN for group-level check
        if ($request->filled('print_status')) {
            switch ($request->print_status) {
                case 'printed':
                    // All kanbans in group must be printed
                    $query->havingRaw('MIN(assy_schedule_circuit.is_printed) = 1');
                    break;
                case 'not_printed':
                    // At least one kanban in group is not printed
                    $query->havingRaw('MIN(assy_schedule_circuit.is_printed) = 0');
                    break;
                case 'all':
                default:
                    // No filter
                    break;
            }
        }
    }
}
