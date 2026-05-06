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
                OR shikake_code LIKE ?
            )", [$escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch]);
        }

        $filteredQuery = clone $query;
        $filteredRecords = DB::table(DB::raw("({$filteredQuery->toSql()}) as sub"))
            ->mergeBindings($filteredQuery)
            ->count();

        // Order - column mapping matches DataTable column indices
        $columns = [
            null,                          // 0 - No (not orderable)
            'master_circuit.cct_no',       // 1 - Type / CCT
            'master_circuit.shikake_code', // 2 - Shikake
            'master_conveyor.conveyor',    // 3 - CV
            'master_circuit.family',       // 4 - Family
            'master_circuit.qty',          // 5 - Qty
            null,                          // 6 - Issue (not orderable)
            'master_circuit.sequence',     // 7 - Seq
            null,                          // 8 - Kanban (not orderable)
            'assy_schedule.shift',         // 9 - CutOff (shift + cutoff)
            null,                          // 10 - # (not orderable)
            null,                          // 11 - Action (not orderable)
        ];

        $hasOrder = false;
        $orderIndex = 0;
        while ($request->has("order.{$orderIndex}.column")) {
            $col = intval($request->input("order.{$orderIndex}.column"));
            $dir = $request->input("order.{$orderIndex}.dir", 'asc');

            if ($col == 9) {
                // Column 9 displays shift/cutoff combined, order by both
                $query->orderBy('assy_schedule.shift', $dir);
                $query->orderBy('cutoff', $dir);
                $hasOrder = true;
            } elseif ($col == 7) {
                // Column 7 is Seq - cast to numeric for proper sorting
                $query->orderBy(DB::raw('CAST(master_circuit.sequence AS UNSIGNED)'), $dir);
                $hasOrder = true;
            } elseif (isset($columns[$col]) && $columns[$col] !== null) {
                $query->orderBy($columns[$col], $dir);
                $hasOrder = true;
            }

            $orderIndex++;
        }

        if (!$hasOrder) {
            // Default: shift asc, cutoff asc, seq asc, cv asc
            $query->orderBy('assy_schedule.shift', 'asc');
            $query->orderBy('cutoff', 'asc');
            $query->orderBy(DB::raw('CAST(master_circuit.sequence AS UNSIGNED)'), 'asc');
            $query->orderBy('master_conveyor.conveyor', 'asc');
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
                'type' => $row->type ?? 'CUTTING',
                'shikake_code' => $row->shikake_code ?? '-',
                'cct_no' => $row->cct_no,
                'cct_code' => $row->cct_code,
                'conveyor' => $row->conveyor,
                'machine' => $row->machine,
                'family' => $row->family,
                'qty' => $row->qty,
                'barcodes' => $row->barcodes ?? '-',
                'issue_count' => $row->issue_count,
                'sequence' => $row->sequence ?? '-',
                'date' => Carbon::parse($row->date)->format('d-m-Y'),
                'shift' => $row->shift,
                'cutoff' => $row->cutoff,
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
                'master_circuit.type',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.family',
                'master_circuit.cust_no',
                'master_circuit.kind',
                'master_circuit.size',
                'master_circuit.col',
                'master_circuit.cl',
                'master_circuit.machine',
                'master_circuit.machine_twist',
                'master_circuit.memory_twist',
                'master_circuit.sequence',
                'master_circuit.sequence_2',
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
                'master_circuit.qty',
                'master_circuit.address',
                'master_circuit.ta',
                'master_circuit.tb',
                'master_circuit.t01',
                'master_circuit.t02',
                'master_circuit.t03',
                'master_circuit.barcode_mesin',
                'master_circuit.barcode_navigasi',
                'master_circuit.barcode_process',
                'master_circuit.barcode_shikake',
                'master_circuit.barcode_twist',
                'master_circuit.qrcode_drawing',
                'master_circuit.shikake_code',
                'master_circuit.to_store',
                'master_circuit.released_note',
                'master_circuit.image_path',
                // Kanban fields from assy_schedule_circuit
                'assy_schedule_circuit.issue',
                'assy_schedule_circuit.barcode_kanban',
                'assy_schedule_circuit.qrcode_shikake',
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
                'master_circuit.type',
                'master_circuit.shikake_code',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.machine',
                'master_circuit.family',
                'master_circuit.qty',
                'master_conveyor.conveyor',
                'master_circuit.sequence',
                'assy_schedule.assy',
                'assy_schedule.schedule as date',
                'assy_schedule.shift',
                DB::raw('MAX(assy_schedule_circuit.cutoff) as cutoff'),
                // Aggregated fields for grouping
                DB::raw('GROUP_CONCAT(assy_schedule_circuit.barcode_kanban ORDER BY assy_schedule_circuit.issue SEPARATOR ", ") as barcodes'),
                DB::raw('GROUP_CONCAT(assy_schedule_circuit.qrcode_shikake ORDER BY assy_schedule_circuit.issue SEPARATOR ", ") as qrcodes_shikake'),
                DB::raw('COUNT(*) as issue_count'),
                // Print status - MIN means all must be printed for group to be "printed"
                DB::raw('MIN(assy_schedule_circuit.is_printed) as is_printed'),
                DB::raw('MAX(assy_schedule_circuit.last_printed_at) as last_printed_at'),
                DB::raw('MAX(assy_schedule_circuit.print_count) as print_count')
            ])
            ->groupBy([
                'assy_schedule_circuit.assy_schedule_id',
                'assy_schedule_circuit.master_circuit_id',
                'master_circuit.type',
                'master_circuit.shikake_code',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.machine',
                'master_circuit.family',
                'master_circuit.qty',
                'master_conveyor.conveyor',
                'master_circuit.sequence',
                'assy_schedule.assy',
                'assy_schedule.schedule',
                'assy_schedule.shift'
            ])
;
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

        // Type filter (CUTTING / CUTTING_TWIST)
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('master_circuit.type', $request->type);
        }

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
