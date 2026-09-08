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
        // Machine, type, and shift are mandatory. Type drives the machine list and
        // shift scopes the progressive (cut off by cut off) print rule, so without
        // all three the list stays empty instead of showing a mixed-scope result.
        if (!$this->hasRequiredFilters($request)) {
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
                OR to_store LIKE ?
                OR machine LIKE ?
                OR family LIKE ?
                OR barcodes LIKE ?
                OR shikake_code LIKE ?
            )", [$escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch, $escapedSearch]);
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

        // Highest cut off whose kanban may be printed right now - a cut off only
        // unlocks once every earlier cut off in the same scope is fully printed.
        $maxPrintableCutoff = $this->getMaxPrintableCutoff($request);

        $result = [];
        foreach ($data as $index => $row) {
            // New Group ID format: assyScheduleId-masterCircuitId
            $groupId = $row->assy_schedule_id . '-' . $row->master_circuit_id;
            $canPrint = (int) $row->cutoff <= $maxPrintableCutoff;

            $result[] = [
                'DT_RowIndex' => $start + $index + 1,
                'assy_schedule_id' => $row->assy_schedule_id,
                'group_id' => $groupId,
                'master_circuit_id' => $row->master_circuit_id,
                'type' => $row->type ?? 'CUTTING',
                'shikake_code' => $row->shikake_code ?? '-',
                'cct_no' => $row->cct_no,
                'cct_code' => $row->cct_code,
                'to_store' => $row->to_store ?? '-',
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
                'can_print' => $canPrint,
                'max_printable_cutoff' => $maxPrintableCutoff,
                'actions' => view('schedule.ekanban_circuit.actions', [
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
                // Must stay named "qty" (not aliased) and listed after assy_schedule.*
                // above so it overrides assy_schedule.qty pulled in by the wildcard —
                // the print ticket must show the per-kanban qty (master_circuit.qty),
                // not the schedule's total qty.
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
                'master_circuit.to_store',
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
                'master_circuit.to_store',
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
     * Check whether any of the given groups has already been printed before
     */
    public function anyAlreadyPrinted(array $groupIds): bool
    {
        foreach ($groupIds as $groupId) {
            $parts = explode('-', $groupId, 2);
            if (count($parts) === 2) {
                $exists = AssyScheduleCircuit::where('assy_schedule_id', $parts[0])
                    ->where('master_circuit_id', $parts[1])
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
        $this->applyScopeFilters($query, $request);

        // Cut off filter
        if ($request->filled('cutoff')) {
            $query->where('assy_schedule_circuit.cutoff', $request->cutoff);
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

    /**
     * Highest cut off number used by the schedule.
     */
    public const MAX_CUTOFF = 5;

    /**
     * The print list needs machine, type, and shift before it shows anything.
     * Type drives the machine dropdown and shift scopes the progressive print
     * rule, so a partial filter set would produce a misleading list.
     */
    public function hasRequiredFilters(Request $request): bool
    {
        return $request->filled('machine')
            && $request->filled('shift')
            && $request->filled('type')
            && $request->type !== 'all';
    }

    /**
     * Filters that define the working scope of the print list (machine, type,
     * area, date, shift). Cut off and print status are deliberately excluded so
     * the same scope can be reused to evaluate the progressive print rule.
     */
    private function applyScopeFilters($query, Request $request)
    {
        // Machine is required
        $query->where('master_circuit.machine', $request->machine);

        // Type filter (CUTTING / CUTTING_TWIST)
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('master_circuit.type', $request->type);
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
    }

    /**
     * Progressive print rule: a cut off can only be printed once every earlier
     * cut off in the same scope (machine + type + area + date + shift) has been
     * fully printed. The lowest cut off that still has an unprinted kanban is
     * therefore the highest one currently printable; when nothing is pending all
     * cut offs are unlocked (admin reprint).
     */
    public function getMaxPrintableCutoff(Request $request): int
    {
        $query = DB::table('assy_schedule_circuit')
            ->join('assy_schedule', 'assy_schedule_circuit.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->where('assy_schedule_circuit.is_printed', 0);

        $this->applyScopeFilters($query, $request);

        $firstPending = $query->min('assy_schedule_circuit.cutoff');

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

            $group = DB::table('assy_schedule_circuit')
                ->join('assy_schedule', 'assy_schedule_circuit.assy_schedule_id', '=', 'assy_schedule.id')
                ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
                ->where('assy_schedule_circuit.assy_schedule_id', $parts[0])
                ->where('assy_schedule_circuit.master_circuit_id', $parts[1])
                ->select([
                    'assy_schedule.schedule',
                    'assy_schedule.shift',
                    'master_circuit.machine',
                    'master_circuit.type',
                    DB::raw('MAX(assy_schedule_circuit.cutoff) as cutoff'),
                ])
                ->groupBy('assy_schedule.schedule', 'assy_schedule.shift', 'master_circuit.machine', 'master_circuit.type')
                ->first();

            if (!$group || $group->cutoff === null) {
                continue;
            }

            $pendingCutoff = DB::table('assy_schedule_circuit')
                ->join('assy_schedule', 'assy_schedule_circuit.assy_schedule_id', '=', 'assy_schedule.id')
                ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
                ->where('assy_schedule.is_lock', '!=', 0)
                ->whereDate('assy_schedule.schedule', $group->schedule)
                ->where('assy_schedule.shift', $group->shift)
                ->where('master_circuit.machine', $group->machine)
                ->where('master_circuit.type', $group->type)
                ->where('assy_schedule_circuit.is_printed', 0)
                ->where('assy_schedule_circuit.cutoff', '<', $group->cutoff)
                ->min('assy_schedule_circuit.cutoff');

            if ($pendingCutoff !== null) {
                return 'Kanban Cut Off ' . $group->cutoff . ' belum bisa diprint. '
                    . 'Selesaikan print Cut Off ' . $pendingCutoff . ' terlebih dahulu.';
            }
        }

        return null;
    }

    /**
     * Machines available for the filter dropdown, scoped by area and type.
     * Read from master_circuit (not master_machine) so the list only offers
     * machines that actually have circuits of the selected type in that area.
     */
    public function getMachineOptions($areaId, $type = null)
    {
        if (!$areaId) {
            return collect([]);
        }

        $query = DB::table('master_circuit')
            ->join('master_conveyor', 'master_circuit.conveyor_id', '=', 'master_conveyor.id')
            ->whereNull('master_circuit.deleted_at')
            ->where('master_conveyor.master_area_id', $areaId)
            ->whereNotNull('master_circuit.machine')
            ->where('master_circuit.machine', '!=', '');

        if ($type && $type !== 'all') {
            $query->where('master_circuit.type', $type);
        }

        return $query->select('master_circuit.machine')
            ->distinct()
            ->orderBy('master_circuit.machine')
            ->pluck('machine');
    }

    /**
     * Base query for the print history - same grouping as the print list but
     * restricted to kanban that has actually been printed, plus the print
     * metadata (when, by whom, how many times).
     */
    private function getPrintHistoryQuery()
    {
        return DB::table('assy_schedule_circuit')
            ->join('assy_schedule', 'assy_schedule_circuit.assy_schedule_id', '=', 'assy_schedule.id')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_circuit', 'assy_schedule_circuit.master_circuit_id', '=', 'master_circuit.id')
            ->leftJoin('users', 'assy_schedule_circuit.last_printed_by', '=', 'users.id')
            ->where('assy_schedule.is_lock', '!=', 0)
            ->where('assy_schedule_circuit.is_printed', 1)
            ->select([
                'assy_schedule_circuit.assy_schedule_id',
                'assy_schedule_circuit.master_circuit_id',
                'master_circuit.type',
                'master_circuit.shikake_code',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.to_store',
                'master_circuit.machine',
                'master_circuit.family',
                'master_circuit.qty',
                'master_conveyor.conveyor',
                'master_circuit.sequence',
                'assy_schedule.assy',
                'assy_schedule.schedule as date',
                'assy_schedule.shift',
                DB::raw('MAX(assy_schedule_circuit.cutoff) as cutoff'),
                DB::raw('GROUP_CONCAT(assy_schedule_circuit.barcode_kanban ORDER BY assy_schedule_circuit.issue SEPARATOR ", ") as barcodes'),
                DB::raw('COUNT(*) as issue_count'),
                DB::raw('MAX(assy_schedule_circuit.last_printed_at) as last_printed_at'),
                DB::raw('MAX(assy_schedule_circuit.print_count) as print_count'),
                DB::raw('MAX(users.name) as printed_by'),
            ])
            ->groupBy([
                'assy_schedule_circuit.assy_schedule_id',
                'assy_schedule_circuit.master_circuit_id',
                'master_circuit.type',
                'master_circuit.shikake_code',
                'master_circuit.cct_no',
                'master_circuit.cct_code',
                'master_circuit.to_store',
                'master_circuit.machine',
                'master_circuit.family',
                'master_circuit.qty',
                'master_conveyor.conveyor',
                'master_circuit.sequence',
                'assy_schedule.assy',
                'assy_schedule.schedule',
                'assy_schedule.shift',
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
            $query->where('master_circuit.machine', $request->machine);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('master_circuit.type', $request->type);
        }

        if ($request->filled('area_id')) {
            $query->where('master_conveyor.master_area_id', $request->area_id);
        }

        if ($request->filled('shift')) {
            $query->where('assy_schedule.shift', $request->shift);
        }

        if ($request->filled('cutoff')) {
            $query->where('assy_schedule_circuit.cutoff', $request->cutoff);
        }

        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end', $dateStart);

        if ($dateStart) {
            $query->whereBetween('assy_schedule_circuit.last_printed_at', [
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
                cct_no LIKE ?
                OR cct_code LIKE ?
                OR to_store LIKE ?
                OR machine LIKE ?
                OR family LIKE ?
                OR barcodes LIKE ?
                OR shikake_code LIKE ?
                OR printed_by LIKE ?
            )", array_fill(0, 8, $escapedSearch));
        }

        $filteredQuery = clone $query;
        $filteredRecords = DB::table(DB::raw("({$filteredQuery->toSql()}) as sub"))
            ->mergeBindings($filteredQuery)
            ->count();

        $query->orderBy(DB::raw('MAX(assy_schedule_circuit.last_printed_at)'), 'desc')
            ->orderBy('assy_schedule.shift', 'asc')
            ->orderBy(DB::raw('MAX(assy_schedule_circuit.cutoff)'), 'asc');

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

        $rows = $query->orderBy(DB::raw('MAX(assy_schedule_circuit.last_printed_at)'), 'desc')->get();

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
            'group_id' => $row->assy_schedule_id . '-' . $row->master_circuit_id,
            'type' => $row->type ?? 'CUTTING',
            'cct_no' => $row->cct_no,
            'cct_code' => $row->cct_code,
            'shikake_code' => $row->shikake_code ?? '-',
            'conveyor' => $row->conveyor,
            'to_store' => $row->to_store ?? '-',
            'family' => $row->family,
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
