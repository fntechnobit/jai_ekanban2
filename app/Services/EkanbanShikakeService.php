<?php

namespace App\Services;

use App\Models\AssyScheduleShikake;
use App\Models\MasterShikakeBonder;
use App\Models\MasterShikakeJoint;
use App\Models\MasterShikakeShield;
use App\Models\MasterShikakeDblCrimp;
use App\Models\MasterShikakeTwist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EkanbanShikakeService
{
    /**
     * Get shikake data for DataTable - Grouped by assy_schedule_id + master_shikake_id
     */
    public function getShikakeDataForTable(Request $request)
    {
        // Require machine filter
        if (!$request->filled('machine')) {
            return [
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ];
        }

        $query = $this->getBaseShikakeQuery();

        // Apply filters
        $this->applyFilters($query, $request);

        // Clone query for counting before search (subquery counting like Circuit service)
        $countQuery = clone $query;
        $totalRecords = DB::table(DB::raw("({$countQuery->toSql()}) as sub"))
            ->mergeBindings($countQuery)
            ->count();

        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $escapedSearch = '%' . addcslashes($searchValue, '%_') . '%';
            $query->havingRaw("(
                identifier LIKE ?
                OR conveyor LIKE ?
                OR barcodes LIKE ?
                OR family LIKE ?
                OR process LIKE ?
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
            'assy_schedule_id',              // 0 - Num (DT_RowIndex)
            'process',                       // 1 - Process
            'identifier',                    // 2 - Code
            'conveyor',                      // 3 - CV
            'family',                        // 4 - Family
            'master_shikake.qty',            // 5 - Qty
            'issue_count',                   // 6 - Issue count
            'barcodes',                      // 7 - Kanban
            'shift',                         // 8 - Shift
            'cutoff'                         // 9 - CO
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

        // Pre-compute min unprinted cutoff per (machine, date, shift) so we can
        // determine if a group has a "previous CO not yet finished".
        $minPendingMap = $this->buildMinPendingCutoffMap($data);

        $result = [];
        foreach ($data as $index => $row) {
            // New Group ID format: assyScheduleId-masterShikakeId
            $groupId = $row->assy_schedule_id . '-' . $row->master_shikake_id;

            $dateKey = Carbon::parse($row->date)->format('Y-m-d');
            $mapKey = $row->machine . '|' . $dateKey . '|' . $row->shift;
            $minPending = $minPendingMap[$mapKey] ?? null;
            $prevCoPending = ($minPending !== null && $row->cutoff !== null && $row->cutoff !== '-' && (int) $row->cutoff > $minPending) ? 1 : 0;
            $row->prev_co_pending = $prevCoPending;

            $result[] = [
                'DT_RowIndex' => $start + $index + 1,
                'assy_schedule_id' => $row->assy_schedule_id,
                'group_id' => $groupId,
                'master_shikake_id' => $row->master_shikake_id,
                'process' => $row->process,
                'identifier' => $row->identifier,
                'conveyor' => $row->conveyor,
                'machine' => $row->machine,
                'family' => $row->family ?? '-',
                'qty' => $row->qty,
                'issue_count' => $row->issue_count,
                'sequence' => $row->sequence ?? '-',
                'barcodes' => $row->barcodes ?? '-',
                'date' => Carbon::parse($row->date)->format('d-m-Y'),
                'shift' => $row->shift,
                'cutoff' => $row->cutoff ?? '-',
                'is_printed' => $row->is_printed,
                'last_printed_at' => $row->last_printed_at,
                'print_count' => $row->print_count ?? 0,
                'prev_co_pending' => $prevCoPending,
                'actions' => view('schedule.ekanban_shikake.actions', ['row' => $row, 'groupId' => $groupId])->render()
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
     * Get shikakes for printing - using group IDs (assyScheduleId-masterShikakeId)
     */
    public function getShikakesForPrint(array $groupIds)
    {
        // Parse group IDs (format: "assyScheduleId-masterShikakeId")
        $conditions = [];
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 2);
            if (count($parts) === 2) {
                $conditions[] = [
                    'assy_schedule_id' => $parts[0],
                    'master_shikake_id' => $parts[1]
                ];
            }
        }

        if (empty($conditions)) {
            return collect([]);
        }

        // Fetch ALL kanban records matching the group criteria
        $shikakes = DB::table('assy_schedule_shikake')
            ->join('assy_schedule', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
            ->leftJoin('listing_stage', 'assy_schedule.listing_id', '=', 'listing_stage.id')
            // Left join child tables to get identifiers
            ->leftJoin('master_shikake_bonder', 'master_shikake.id', '=', 'master_shikake_bonder.master_shikake_id')
            ->leftJoin('master_shikake_joint', 'master_shikake.id', '=', 'master_shikake_joint.master_shikake_id')
            ->leftJoin('master_shikake_shield', 'master_shikake.id', '=', 'master_shikake_shield.master_shikake_id')
            ->leftJoin('master_shikake_dbl_crimp', 'master_shikake.id', '=', 'master_shikake_dbl_crimp.master_shikake_id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->where(function($query) use ($conditions) {
                foreach ($conditions as $condition) {
                    $query->orWhere(function($q) use ($condition) {
                        $q->where('assy_schedule_shikake.assy_schedule_id', $condition['assy_schedule_id'])
                          ->where('assy_schedule_shikake.master_shikake_id', $condition['master_shikake_id']);
                    });
                }
            })
            ->select([
                'assy_schedule.*',
                'assy_schedule.id as assy_schedule_id',
                'master_conveyor.conveyor',
                'master_conveyor.pallet_qty',
                'listing_stage.carline',
                'master_shikake.id as shikake_id',
                'master_shikake.process',
                'master_shikake.conveyor as shikake_conveyor',
                'master_shikake.machine',
                'master_shikake.qty as shikake_qty',
                'master_shikake.family',
                'master_shikake.sequence',
                'master_shikake.image_path',
                'master_shikake.released_note',
                // Kanban fields from assy_schedule_shikake
                'assy_schedule_shikake.issue',
                'assy_schedule_shikake.barcode_kanban',
                'assy_schedule_shikake.release_date',
                'assy_schedule_shikake.qty_listing',
                'assy_schedule_shikake.qty_kanban',
                'assy_schedule_shikake.cutoff as kanban_cutoff',
                // Identifier fields from child tables
                'master_shikake_bonder.bonder_no',
                'master_shikake_joint.bonder_no as joint_bonder_no',
                'master_shikake_shield.shield_no',
                'master_shikake_dbl_crimp.drawing_no as dbl_crimp_drawing_no',
                DB::raw('CEIL(assy_schedule.qty / NULLIF(master_conveyor.pallet_qty, 0)) as pallet_count')
            ])
            ->orderBy('master_shikake.process')
            ->orderBy('assy_schedule_shikake.issue')
            ->get();

        // Load process-specific details for each shikake
        foreach ($shikakes as $shikake) {
            $shikake->details = $this->loadProcessDetails($shikake->shikake_id, $shikake->process);
            $shikake->identifier = $this->getIdentifierByProcess($shikake);
        }

        return $shikakes;
    }

    /**
     * Get identifier based on process type
     */
    private function getIdentifierByProcess($row)
    {
        switch ($row->process) {
            case 'BONDER':
                return $row->bonder_no ?? '-';
            case 'JOINT':
                return $row->joint_bonder_no ?? '-';
            case 'SHIELD':
                return $row->shield_no ?? '-';
            case 'DBL CRIMP':
                return $row->dbl_crimp_drawing_no ?? '-';
            default:
                return '-';
        }
    }

    /**
     * Load process-specific details from child tables
     */
    public function loadProcessDetails($shikakeId, $process)
    {
        switch ($process) {
            case 'BONDER':
                return MasterShikakeBonder::where('master_shikake_id', $shikakeId)->first();
            case 'JOINT':
                return MasterShikakeJoint::where('master_shikake_id', $shikakeId)->first();
            case 'SHIELD':
                return MasterShikakeShield::where('master_shikake_id', $shikakeId)->first();
            case 'DBL CRIMP':
                return MasterShikakeDblCrimp::where('master_shikake_id', $shikakeId)->first();
            case 'TWIST':
                return MasterShikakeTwist::where('master_shikake_id', $shikakeId)->first();
            default:
                return null;
        }
    }

    /**
     * Get base shikake query - Main table is assy_schedule_shikake, grouped by assy_schedule_id + master_shikake_id
     */
    private function getBaseShikakeQuery()
    {
        return DB::table('assy_schedule_shikake')
            ->join('assy_schedule', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
            // Left join child tables to get identifiers (TWIST moved to circuit module)
            ->leftJoin('master_shikake_bonder', 'master_shikake.id', '=', 'master_shikake_bonder.master_shikake_id')
            ->leftJoin('master_shikake_joint', 'master_shikake.id', '=', 'master_shikake_joint.master_shikake_id')
            ->leftJoin('master_shikake_shield', 'master_shikake.id', '=', 'master_shikake_shield.master_shikake_id')
            ->leftJoin('master_shikake_dbl_crimp', 'master_shikake.id', '=', 'master_shikake_dbl_crimp.master_shikake_id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->select([
                'assy_schedule_shikake.assy_schedule_id',
                'assy_schedule_shikake.master_shikake_id',
                'master_shikake.process',
                'master_shikake.machine',
                'master_shikake.family',
                'master_shikake.qty',
                'master_shikake.sequence',
                'master_conveyor.conveyor',
                'master_conveyor.pallet_qty',
                'assy_schedule.assy',
                'assy_schedule.schedule as date',
                'assy_schedule.shift',
                DB::raw('MAX(assy_schedule_shikake.cutoff) as cutoff'),
                // Computed identifier based on process type
                DB::raw("COALESCE(
                    master_shikake_bonder.bonder_no,
                    master_shikake_joint.bonder_no,
                    master_shikake_shield.shield_no,
                    master_shikake_dbl_crimp.drawing_no,
                    '-'
                ) as identifier"),
                // Aggregated fields for grouping
                DB::raw('GROUP_CONCAT(assy_schedule_shikake.barcode_kanban ORDER BY assy_schedule_shikake.issue SEPARATOR ", ") as barcodes'),
                DB::raw('COUNT(*) as issue_count'),
                // Print status - MIN means all must be printed for group to be "printed"
                DB::raw('MIN(assy_schedule_shikake.is_printed) as is_printed'),
                DB::raw('MAX(assy_schedule_shikake.last_printed_at) as last_printed_at'),
                DB::raw('MAX(assy_schedule_shikake.print_count) as print_count')
            ])
            ->groupBy([
                'assy_schedule_shikake.assy_schedule_id',
                'assy_schedule_shikake.master_shikake_id',
                'master_shikake.process',
                'master_shikake.machine',
                'master_shikake.family',
                'master_shikake.qty',
                'master_shikake.sequence',
                'master_conveyor.conveyor',
                'master_conveyor.pallet_qty',
                'assy_schedule.assy',
                'assy_schedule.schedule',
                'assy_schedule.shift',
                DB::raw("COALESCE(
                    master_shikake_bonder.bonder_no,
                    master_shikake_joint.bonder_no,
                    master_shikake_shield.shield_no,
                    master_shikake_dbl_crimp.drawing_no,
                    '-'
                )")
            ])
            ->orderBy('assy_schedule.shift', 'ASC')
            ->orderBy(DB::raw('MAX(assy_schedule_shikake.cutoff)'), 'ASC')
            ->orderBy('master_shikake.sequence', 'ASC')
            ->orderBy('master_conveyor.conveyor', 'ASC');
    }

    /**
     * Mark shikake group as printed - updates ALL rows in the group
     */
    public function markAsPrinted(array $groupIds, $userId)
    {
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 2);
            if (count($parts) === 2) {
                $assyScheduleId = $parts[0];
                $masterShikakeId = $parts[1];
                
                // Update ALL kanban rows in this group
                AssyScheduleShikake::where('assy_schedule_id', $assyScheduleId)
                    ->where('master_shikake_id', $masterShikakeId)
                    ->update([
                        'is_printed' => true,
                        'last_printed_at' => now(),
                        'last_printed_by' => $userId,
                        'print_count' => DB::raw('COALESCE(print_count, 0) + 1')
                    ]);
            }
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        // Machine filter (required)
        $query->where('master_shikake.machine', $request->machine);

        // Process type filter
        if ($request->filled('process')) {
            $query->where('master_shikake.process', $request->process);
        }

        // Area filter
        if ($request->filled('area_id')) {
            $query->where('master_conveyor.master_area_id', $request->area_id);
        }

        // Cut off filter
        if ($request->filled('cutoff')) {
            $query->where('assy_schedule_shikake.cutoff', $request->cutoff);
        }

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
                    $query->havingRaw('MIN(assy_schedule_shikake.is_printed) = 1');
                    break;
                case 'not_printed':
                    // At least one kanban in group is not printed
                    $query->havingRaw('MIN(assy_schedule_shikake.is_printed) = 0');
                    break;
                case 'all':
                default:
                    // No filter
                    break;
            }
        }
    }

    /**
     * Build a map of minimum cutoff that still has unprinted kanbans for each
     * (machine, date, shift) combination present in the given rows.
     * Key format: "machine|YYYY-MM-DD|shift" -> int (min unprinted cutoff)
     */
    private function buildMinPendingCutoffMap($rows)
    {
        $keys = [];
        foreach ($rows as $row) {
            $dateKey = Carbon::parse($row->date)->format('Y-m-d');
            $keys[$row->machine . '|' . $dateKey . '|' . $row->shift] = [
                'machine' => $row->machine,
                'date' => $dateKey,
                'shift' => $row->shift,
            ];
        }
        if (empty($keys)) {
            return [];
        }

        $map = [];
        foreach ($keys as $key => $g) {
            $map[$key] = $this->getMinUnprintedCutoff($g['machine'], $g['date'], $g['shift']);
        }
        return $map;
    }

    /**
     * Return the minimum cutoff (machine/date/shift) that still has at least
     * one unprinted kanban. Returns null if all printed.
     */
    public function getMinUnprintedCutoff($machine, $date, $shift)
    {
        $value = DB::table('assy_schedule_shikake')
            ->join('assy_schedule', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
            ->where('master_shikake.machine', $machine)
            ->whereDate('assy_schedule.schedule', $date)
            ->where('assy_schedule.shift', $shift)
            ->where('assy_schedule_shikake.is_printed', 0)
            ->min('assy_schedule_shikake.cutoff');

        return $value !== null ? (int) $value : null;
    }

    /**
     * Check if any group violates the "previous CO must be finished" rule.
     * Returns the first violating group info or null.
     */
    public function findPriorCutoffViolation(array $groupIds)
    {
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$assyScheduleId, $masterShikakeId] = $parts;

            $info = DB::table('assy_schedule_shikake')
                ->join('assy_schedule', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
                ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
                ->where('assy_schedule_shikake.assy_schedule_id', $assyScheduleId)
                ->where('assy_schedule_shikake.master_shikake_id', $masterShikakeId)
                ->select(
                    'master_shikake.machine',
                    'assy_schedule.schedule as date',
                    'assy_schedule.shift',
                    DB::raw('MIN(assy_schedule_shikake.cutoff) as cutoff')
                )
                ->groupBy('master_shikake.machine', 'assy_schedule.schedule', 'assy_schedule.shift')
                ->first();

            if (!$info) {
                continue;
            }

            $date = Carbon::parse($info->date)->format('Y-m-d');
            $minPending = $this->getMinUnprintedCutoff($info->machine, $date, $info->shift);

            if ($minPending !== null && $info->cutoff !== null && (int) $info->cutoff > $minPending) {
                return [
                    'group_id' => $groupId,
                    'machine' => $info->machine,
                    'date' => $date,
                    'shift' => $info->shift,
                    'cutoff' => (int) $info->cutoff,
                    'pending_cutoff' => $minPending,
                ];
            }
        }
        return null;
    }
}
