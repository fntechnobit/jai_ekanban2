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
        // Machine, process, and shift are mandatory. Process drives the machine
        // list and shift scopes the progressive (cut off by cut off) print rule,
        // so without all three the list stays empty instead of showing a mixed
        // scope result.
        if (!$this->hasRequiredFilters($request)) {
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

        // Highest cut off whose kanban may be printed right now - a cut off only
        // unlocks once every earlier cut off in the same scope is fully printed.
        $maxPrintableCutoff = $this->getMaxPrintableCutoff($request);

        $result = [];
        foreach ($data as $index => $row) {
            // New Group ID format: assyScheduleId-masterShikakeId
            $groupId = $row->assy_schedule_id . '-' . $row->master_shikake_id;
            $canPrint = (int) $row->cutoff <= $maxPrintableCutoff;

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
                'can_print' => $canPrint,
                'max_printable_cutoff' => $maxPrintableCutoff,
                'actions' => view('schedule.ekanban_shikake.actions', [
                    'row' => $row,
                    'groupId' => $groupId,
                    'canPrint' => $canPrint,
                    'maxPrintableCutoff' => $maxPrintableCutoff,
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
            // Only reference live masters (raw joins bypass the SoftDeletes scope).
            ->whereNull('master_shikake.deleted_at')
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
                // Must be aliased to the same name "qty" (not shikake_qty) so it is
                // returned last and overrides assy_schedule.qty pulled in by the
                // assy_schedule.* wildcard above — the print ticket must show the
                // per-kanban qty (master_shikake.qty), not the schedule's total qty.
                'master_shikake.qty as qty',
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
            // Only reference live masters; raw joins ignore the SoftDeletes scope,
            // so exclude soft-deleted masters explicitly to stay consistent with edit.
            ->whereNull('master_shikake.deleted_at')
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
     * Check whether any of the given groups has already been printed before
     */
    public function anyAlreadyPrinted(array $groupIds): bool
    {
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 2);
            if (count($parts) === 2) {
                $exists = AssyScheduleShikake::where('assy_schedule_id', $parts[0])
                    ->where('master_shikake_id', $parts[1])
                    ->where('is_printed', true)
                    ->exists();
                if ($exists) {
                    return true;
                }
            }
        }

        return false;
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
        $this->applyScopeFilters($query, $request);

        // Cut off filter
        if ($request->filled('cutoff')) {
            $query->where('assy_schedule_shikake.cutoff', $request->cutoff);
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
     * Highest cut off number used by the schedule.
     */
    public const MAX_CUTOFF = 5;

    /**
     * The print list needs machine, process, and shift before it shows anything.
     * Process drives the machine dropdown and shift scopes the progressive print
     * rule, so a partial filter set would produce a misleading list.
     */
    public function hasRequiredFilters(Request $request): bool
    {
        return $request->filled('machine')
            && $request->filled('shift')
            && $request->filled('process')
            && $request->process !== 'all';
    }

    /**
     * Filters that define the working scope of the print list (machine, process,
     * area, date, shift). Cut off and print status are deliberately excluded so
     * the same scope can be reused to evaluate the progressive print rule.
     */
    private function applyScopeFilters($query, Request $request)
    {
        // Machine filter (required)
        $query->where('master_shikake.machine', $request->machine);

        // Process type filter
        if ($request->filled('process') && $request->process !== 'all') {
            $query->where('master_shikake.process', $request->process);
        }

        // Area filter
        if ($request->filled('area_id')) {
            $query->where('master_conveyor.master_area_id', $request->area_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('assy_schedule.schedule', $request->date);
        }

        if ($request->filled('shift')) {
            $query->where('assy_schedule.shift', $request->shift);
        }
    }

    /**
     * Progressive print rule: a cut off can only be printed once every earlier
     * cut off in the same scope (machine + process + area + date + shift) has
     * been fully printed. The lowest cut off that still has an unprinted kanban
     * is therefore the highest one currently printable; when nothing is pending
     * all cut offs are unlocked (admin reprint).
     */
    public function getMaxPrintableCutoff(Request $request): int
    {
        $query = DB::table('assy_schedule_shikake')
            ->join('assy_schedule', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->whereNull('master_shikake.deleted_at')
            ->where('assy_schedule_shikake.is_printed', 0);

        $this->applyScopeFilters($query, $request);

        $firstPending = $query->min('assy_schedule_shikake.cutoff');

        return $firstPending === null ? self::MAX_CUTOFF : (int) $firstPending;
    }

    /**
     * Server-side guard for the progressive print rule - returns an error
     * message when any of the groups belongs to a cut off that is still locked
     * by an earlier, unfinished cut off. Returns null when printing is allowed.
     */
    public function getCutoffOrderViolation(array $groupIds): ?string
    {
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $group = DB::table('assy_schedule_shikake')
                ->join('assy_schedule', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
                ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
                ->where('assy_schedule_shikake.assy_schedule_id', $parts[0])
                ->where('assy_schedule_shikake.master_shikake_id', $parts[1])
                ->select([
                    'assy_schedule.schedule',
                    'assy_schedule.shift',
                    'master_shikake.machine',
                    'master_shikake.process',
                    DB::raw('MAX(assy_schedule_shikake.cutoff) as cutoff'),
                ])
                ->groupBy('assy_schedule.schedule', 'assy_schedule.shift', 'master_shikake.machine', 'master_shikake.process')
                ->first();

            if (!$group || $group->cutoff === null) {
                continue;
            }

            $pendingCutoff = DB::table('assy_schedule_shikake')
                ->join('assy_schedule', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
                ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
                ->where('assy_schedule.is_lock', '!=', 0)
                ->whereNull('master_shikake.deleted_at')
                ->whereDate('assy_schedule.schedule', $group->schedule)
                ->where('assy_schedule.shift', $group->shift)
                ->where('master_shikake.machine', $group->machine)
                ->where('master_shikake.process', $group->process)
                ->where('assy_schedule_shikake.is_printed', 0)
                ->where('assy_schedule_shikake.cutoff', '<', $group->cutoff)
                ->min('assy_schedule_shikake.cutoff');

            if ($pendingCutoff !== null) {
                return 'Kanban Cut Off ' . $group->cutoff . ' belum bisa diprint. '
                    . 'Selesaikan print Cut Off ' . $pendingCutoff . ' terlebih dahulu.';
            }
        }

        return null;
    }

    /**
     * Machines available for the filter dropdown, scoped by area and process.
     */
    public function getMachineOptions($areaId, $process = null)
    {
        if (!$areaId) {
            return collect([]);
        }

        $query = DB::table('master_shikake')
            ->join('master_conveyor', 'master_shikake.conveyor_id', '=', 'master_conveyor.id')
            ->whereNull('master_shikake.deleted_at')
            ->where('master_conveyor.master_area_id', $areaId)
            ->whereNotNull('master_shikake.machine')
            ->where('master_shikake.machine', '!=', '');

        if ($process && $process !== 'all') {
            $query->where('master_shikake.process', $process);
        }

        return $query->select('master_shikake.machine')
            ->distinct()
            ->orderBy('master_shikake.machine')
            ->pluck('machine');
    }

    /**
     * Base query for the print history - same grouping as the print list but
     * restricted to kanban that has actually been printed, plus the print
     * metadata (when, by whom, how many times).
     */
    private function getPrintHistoryQuery()
    {
        return DB::table('assy_schedule_shikake')
            ->join('assy_schedule', 'assy_schedule_shikake.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_shikake', 'assy_schedule_shikake.master_shikake_id', '=', 'master_shikake.id')
            ->leftJoin('master_shikake_bonder', 'master_shikake.id', '=', 'master_shikake_bonder.master_shikake_id')
            ->leftJoin('master_shikake_joint', 'master_shikake.id', '=', 'master_shikake_joint.master_shikake_id')
            ->leftJoin('master_shikake_shield', 'master_shikake.id', '=', 'master_shikake_shield.master_shikake_id')
            ->leftJoin('master_shikake_dbl_crimp', 'master_shikake.id', '=', 'master_shikake_dbl_crimp.master_shikake_id')
            ->leftJoin('users', 'assy_schedule_shikake.last_printed_by', '=', 'users.id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->whereNull('master_shikake.deleted_at')
            ->where('assy_schedule_shikake.is_printed', 1)
            ->select([
                'assy_schedule_shikake.assy_schedule_id',
                'assy_schedule_shikake.master_shikake_id',
                'master_shikake.process',
                'master_shikake.machine',
                'master_shikake.family',
                'master_shikake.qty',
                'master_shikake.sequence',
                'master_conveyor.conveyor',
                'assy_schedule.assy',
                'assy_schedule.schedule as date',
                'assy_schedule.shift',
                DB::raw('MAX(assy_schedule_shikake.cutoff) as cutoff'),
                DB::raw("COALESCE(
                    master_shikake_bonder.bonder_no,
                    master_shikake_joint.bonder_no,
                    master_shikake_shield.shield_no,
                    master_shikake_dbl_crimp.drawing_no,
                    '-'
                ) as identifier"),
                DB::raw('GROUP_CONCAT(assy_schedule_shikake.barcode_kanban ORDER BY assy_schedule_shikake.issue SEPARATOR ", ") as barcodes'),
                DB::raw('COUNT(*) as issue_count'),
                DB::raw('MAX(assy_schedule_shikake.last_printed_at) as last_printed_at'),
                DB::raw('MAX(assy_schedule_shikake.print_count) as print_count'),
                DB::raw('MAX(users.name) as printed_by'),
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
                'assy_schedule.assy',
                'assy_schedule.schedule',
                'assy_schedule.shift',
                DB::raw("COALESCE(
                    master_shikake_bonder.bonder_no,
                    master_shikake_joint.bonder_no,
                    master_shikake_shield.shield_no,
                    master_shikake_dbl_crimp.drawing_no,
                    '-'
                )"),
            ]);
    }

    /**
     * History filters - every filter is optional here (machine included). The
     * date range always reads the actual print date, which is what the history
     * screen is about.
     */
    private function applyHistoryFilters($query, Request $request)
    {
        if ($request->filled('machine') && $request->machine !== 'all') {
            $query->where('master_shikake.machine', $request->machine);
        }

        if ($request->filled('process') && $request->process !== 'all') {
            $query->where('master_shikake.process', $request->process);
        }

        if ($request->filled('area_id')) {
            $query->where('master_conveyor.master_area_id', $request->area_id);
        }

        if ($request->filled('shift')) {
            $query->where('assy_schedule.shift', $request->shift);
        }

        if ($request->filled('cutoff')) {
            $query->where('assy_schedule_shikake.cutoff', $request->cutoff);
        }

        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end', $dateStart);

        if ($dateStart) {
            $query->whereBetween('assy_schedule_shikake.last_printed_at', [
                Carbon::parse($dateStart)->startOfDay(),
                Carbon::parse($dateEnd)->endOfDay(),
            ]);
        }
    }

    /**
     * Print history for DataTable
     */
    public function getPrintHistoryForTable(Request $request)
    {
        $query = $this->getPrintHistoryQuery();
        $this->applyHistoryFilters($query, $request);

        $countQuery = clone $query;
        $totalRecords = DB::table(DB::raw("({$countQuery->toSql()}) as sub"))
            ->mergeBindings($countQuery)
            ->count();

        if ($request->filled('search.value')) {
            $escapedSearch = '%' . addcslashes($request->input('search.value'), '%_') . '%';
            $query->havingRaw("(
                identifier LIKE ?
                OR conveyor LIKE ?
                OR barcodes LIKE ?
                OR family LIKE ?
                OR process LIKE ?
                OR machine LIKE ?
                OR printed_by LIKE ?
            )", array_fill(0, 7, $escapedSearch));
        }

        $filteredQuery = clone $query;
        $filteredRecords = DB::table(DB::raw("({$filteredQuery->toSql()}) as sub"))
            ->mergeBindings($filteredQuery)
            ->count();

        $query->orderBy(DB::raw('MAX(assy_schedule_shikake.last_printed_at)'), 'desc')
            ->orderBy('assy_schedule.shift', 'asc')
            ->orderBy(DB::raw('MAX(assy_schedule_shikake.cutoff)'), 'asc');

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $data = $length == -1 ? $query->get() : $query->skip($start)->take($length)->get();

        $result = [];
        foreach ($data as $index => $row) {
            $result[] = $this->formatHistoryRow($row, $start + $index + 1);
        }

        return [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $result,
        ];
    }

    /**
     * Full (unpaginated) history rows - used by the Excel export
     */
    public function getPrintHistoryRows(Request $request)
    {
        $query = $this->getPrintHistoryQuery();
        $this->applyHistoryFilters($query, $request);

        $rows = $query->orderBy(DB::raw('MAX(assy_schedule_shikake.last_printed_at)'), 'desc')->get();

        return $rows->values()->map(function ($row, $index) {
            return $this->formatHistoryRow($row, $index + 1);
        });
    }

    /**
     * Shape one history row for both the DataTable and the Excel export
     */
    private function formatHistoryRow($row, int $rowIndex): array
    {
        return [
            'DT_RowIndex' => $rowIndex,
            'group_id' => $row->assy_schedule_id . '-' . $row->master_shikake_id,
            'process' => $row->process,
            'identifier' => $row->identifier ?? '-',
            'conveyor' => $row->conveyor,
            'family' => $row->family ?? '-',
            'qty' => $row->qty,
            'issue_count' => $row->issue_count,
            'sequence' => $row->sequence ?? '-',
            'barcodes' => $row->barcodes ?? '-',
            'machine' => $row->machine,
            'date' => $row->date ? Carbon::parse($row->date)->format('d-m-Y') : '-',
            'shift' => $row->shift,
            'cutoff' => $row->cutoff,
            'printed_at' => $row->last_printed_at
                ? Carbon::parse($row->last_printed_at)->format('d-m-Y H:i')
                : '-',
            'printed_by' => $row->printed_by ?? '-',
            'print_count' => $row->print_count ?? 0,
        ];
    }
}
