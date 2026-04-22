<?php

namespace App\Services;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use App\Services\Schedule\ShiftCapacityCalculator;
use App\Services\Schedule\ShiftLockChecker;
use App\Services\Schedule\ListingAllocator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleVerificationService
{
    protected KanbanGeneratorService $kanbanGenerator;
    protected ShiftCapacityCalculator $capacityCalculator;
    protected ShiftLockChecker $lockChecker;
    protected ListingAllocator $listingAllocator;

    public function __construct(
        KanbanGeneratorService $kanbanGenerator,
        ShiftCapacityCalculator $capacityCalculator,
        ShiftLockChecker $lockChecker,
        ListingAllocator $listingAllocator
    ) {
        $this->kanbanGenerator = $kanbanGenerator;
        $this->capacityCalculator = $capacityCalculator;
        $this->lockChecker = $lockChecker;
        $this->listingAllocator = $listingAllocator;
    }

    /**
     * Get datatable query for schedule verification.
     * Generates ALL date×conveyor×shift combinations for "active" conveyors
     * (those with any assy_schedule data in the range), then LEFT JOINs with actual
     * assy data so gap dates appear as "No Data" rows.
     */
    public function getDatatableQuery($startDate = null, $endDate = null, $conveyorId = null, $status = null)
    {
        // --- Step 1: Determine date range ---
        $start = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $end   = $endDate   ? Carbon::parse($endDate)   : Carbon::today()->addDays(10);

        // --- Step 2: Get ACTIVE conveyors (those with any assy_schedule in range) ---
        $conveyorQuery = DB::table('assy_schedule AS a')
            ->join('master_conveyor AS mc', 'a.conveyor_id', '=', 'mc.id')
            ->whereNull('mc.deleted_at')
            ->whereRaw('DATE(a.schedule) BETWEEN ? AND ?', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->select('mc.id AS conveyor_id', 'mc.conveyor AS conveyor_name', 'mc.capacity', 'mc.shift_qty', 'mc.shift_start')
            ->distinct();

        if ($conveyorId) {
            $conveyorQuery->where('mc.id', $conveyorId);
        }

        $activeConveyors = $conveyorQuery->get();

        if ($activeConveyors->isEmpty()) {
            return collect();
        }

        // --- Step 3: Get actual assy_schedule aggregated data for the range ---
        $assyQuery = DB::table('assy_schedule')
            ->selectRaw('DATE(schedule) AS schedule_date, conveyor_id, shift, GROUP_CONCAT(DISTINCT assy ORDER BY assy SEPARATOR ", ") AS assy_list, SUM(qty) AS total_listing, MAX(is_lock) AS is_lock, MIN(id) AS first_id')
            ->whereRaw('DATE(schedule) BETWEEN ? AND ?', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->groupByRaw('DATE(schedule), conveyor_id, shift');

        if ($conveyorId) {
            $assyQuery->where('conveyor_id', $conveyorId);
        }

        // Index actual data by "date|conveyor_id|shift" for O(1) lookup
        $assyData = [];
        foreach ($assyQuery->get() as $row) {
            $key = $row->schedule_date . '|' . $row->conveyor_id . '|' . $row->shift;
            $assyData[$key] = $row;
        }

        // --- Step 4: Generate full grid: all dates × active conveyors × shifts ---
        $rows = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');

            foreach ($activeConveyors as $conv) {
                $shiftStart = (int) ($conv->shift_start ?? 1);
                $shiftQty   = (int) ($conv->shift_qty   ?? 1);

                for ($s = $shiftStart; $s < $shiftStart + $shiftQty; $s++) {
                    $key   = $dateStr . '|' . $conv->conveyor_id . '|' . $s;
                    $assy  = $assyData[$key] ?? null;

                    $hasAssy  = $assy !== null ? 1 : 0;
                    $isLock   = $assy ? (int) $assy->is_lock : 0;

                    // Apply status filter
                    if ($status === 'verified'  && !($hasAssy && $isLock == 1)) continue;
                    if ($status === 'pending'   && !($hasAssy && $isLock == 0)) continue;
                    if ($status === 'no_data'   && $hasAssy) continue;

                    $rows[] = (object) [
                        'schedule_date' => $dateStr,
                        'conveyor_id'   => $conv->conveyor_id,
                        'conveyor_name' => $conv->conveyor_name,
                        'capacity'      => $conv->capacity,
                        'shift'         => $s,
                        'total_listing' => $assy ? (int) $assy->total_listing : 0,
                        'assy_list'     => $assy ? ($assy->assy_list ?? '') : '',
                        'is_lock'       => $isLock,
                        'first_id'      => $assy ? $assy->first_id : null,
                        'has_assy'      => $hasAssy,
                    ];
                }
            }

            $current->addDay();
        }

        return collect($rows);
    }

    /**
     * Get available dates (H to H+days_range) that have unverified schedules for a conveyor
     * Used by the right panel date selector in verification modal
     */
    public function getAvailableDates($conveyorId, $currentDate, $currentShift, $daysRange = 10)
    {
        $endDate = Carbon::parse($currentDate)->addDays((int) $daysRange)->format('Y-m-d');

        $rows = DB::select("
            SELECT 
                DATE(schedule) AS schedule_date,
                shift,
                COUNT(*) AS item_count,
                SUM(qty) AS total_qty
            FROM assy_schedule
            WHERE conveyor_id = ?
              AND DATE(schedule) >= ?
              AND DATE(schedule) <= ?
              AND is_lock = 0
              AND verified_at IS NULL
              AND NOT (DATE(schedule) = ? AND shift = ?)
            GROUP BY DATE(schedule), shift
            ORDER BY DATE(schedule) ASC, shift ASC
        ", [$conveyorId, $currentDate, $endDate, $currentDate, $currentShift]);

        return [
            'success' => true,
            'data' => $rows
        ];
    }

    /**
     * Get verification details for a specific schedule
     */
    public function getVerificationDetails($conveyorId, $date, $shift)
    {
        // Get the conveyor
        $conveyor = MasterConveyor::find($conveyorId);
        
        if (!$conveyor) {
            return [
                'success' => false,
                'message' => 'Conveyor not found'
            ];
        }

        // Get all schedules for this conveyor, date, and shift
        $schedules = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->orderBy('cutoff', 'asc')
            ->orderBy('listing_id', 'asc')
            ->get();

        // Calculate capacities
        $normalCutOffCapacity = round($conveyor->capacity / 4, 2);
        $cutOff5Capacity      = round($normalCutOffCapacity * 0.875, 2);

        if ($schedules->isEmpty()) {
            // Return success with empty cut-offs so the modal can open
            // (allows user to drag data from other dates into this empty slot)
            return [
                'success'               => true,
                'conveyor_id'           => $conveyorId,
                'conveyor'              => $conveyor->conveyor,
                'date'                  => $date,
                'shift'                 => $shift,
                'capacity'              => $conveyor->capacity,
                'normal_cutoff_capacity'=> $normalCutOffCapacity,
                'cutoff5_capacity'      => $cutOff5Capacity,
                'assy_count'            => 0,
                'total_listing'         => 0,
                'cut_offs'              => array_map(fn($i) => ['cutoff' => $i, 'items' => []], range(1, 5)),
                'is_empty'              => true,
            ];
        }

        // Group by cut off
        $cutOffs = $schedules->groupBy('cutoff')->map(function ($items, $cutoff) {
            return [
                'cutoff' => $cutoff,
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'listing_id' => $item->listing_id,
                        'assy' => $item->assy,
                        'assycode' => $item->assycode,
                        'qty' => $item->qty,
                        'cutoff' => $item->cutoff,
                        'seq' => $item->seq,
                        'plt' => $item->plt,
                        'mode' => $item->mode,
                        'snp' => $item->snp,
                        'snpa' => $item->snpa,
                        'transferred_from_date' => $item->transferred_from_date
                            ? Carbon::parse($item->transferred_from_date)->format('Y-m-d')
                            : null,
                        'transferred_from_shift'  => $item->transferred_from_shift,
                        'transferred_from_cutoff' => $item->transferred_from_cutoff,
                    ];
                })->values()->toArray()
            ];
        })->values()->toArray();

        // Always ensure Cut Off 5 exists
        $hasCutOff5 = false;
        foreach ($cutOffs as $co) {
            if ($co['cutoff'] == 5) {
                $hasCutOff5 = true;
                break;
            }
        }
        
        if (!$hasCutOff5) {
            $cutOffs[] = [
                'cutoff' => 5,
                'items' => []
            ];
        }
        
        // Sort by cutoff
        usort($cutOffs, function($a, $b) {
            return $a['cutoff'] - $b['cutoff'];
        });

        // Get unique assy count
        $assyCount = $schedules->pluck('assy')->unique()->count();
        $totalListing = $schedules->sum('qty');

        return [
            'success' => true,
            'conveyor_id' => $conveyorId,
            'conveyor' => $conveyor->conveyor,
            'date' => $date,
            'shift' => $shift,
            'capacity' => $conveyor->capacity,
            'normal_cutoff_capacity' => round($normalCutOffCapacity, 2),
            'cutoff5_capacity' => $cutOff5Capacity,
            'assy_count' => $assyCount,
            'total_listing' => $totalListing,
            'cut_offs' => $cutOffs
        ];
    }

    /**
     * Get available assy data for a specific date and shift
     * IMPORTANT: Only returns UNVERIFIED schedules (verified_at IS NULL AND is_lock = 0)
     */
    public function getAvailableAssyData($conveyorId, $date, $shift)
    {
        \Log::info("getAvailableAssyData called", [
            'conveyor_id' => $conveyorId,
            'date' => $date,
            'shift' => $shift
        ]);

        // Get schedules for specific date and shift (or all shifts), grouped by cut-off
        // CRITICAL FILTERS:
        // 1. is_lock = 0 (unlocked schedules)
        // 2. verified_at IS NULL (not verified yet)
        $query = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)  // Use whereDate for datetime column
            ->where('is_lock', 0)
            ->where(function($q) {
                $q->whereNull('verified_at')
                  ->orWhere('verified_at', '');
            });
        
        // Only filter by shift if not 'all'
        if ($shift !== 'all' && $shift !== null && $shift !== '') {
            $query->where('shift', $shift);
        }
        
        // Log the actual SQL query
        \Log::info("SQL Query", [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);
        
        // SORT ORDER: shift (ASC) -> cutoff (ASC) -> listing_id (ASC = urutan dari listing sumber)
        $schedules = $query->select('id', 'assy', 'qty', 'cutoff', 'shift', 'listing_id', 'assycode', 'seq', 'plt', 'mode', 'snp', 'snpa', 'verified_at', 'is_lock')
            ->orderBy('shift', 'asc')
            ->orderBy('cutoff', 'asc')
            ->orderBy('listing_id', 'asc')
            ->get();

        \Log::info("Query executed", [
            'total_records' => $schedules->count(),
            'sample_first' => $schedules->first() ? [
                'id' => $schedules->first()->id,
                'assy' => $schedules->first()->assy,
                'verified_at' => $schedules->first()->verified_at,
                'is_lock' => $schedules->first()->is_lock
            ] : null
        ]);

        // CRITICAL: Additional filter in collection to ensure no verified data
        $schedules = $schedules->filter(function($item) {
            $isUnverified = is_null($item->verified_at) || $item->verified_at === '';
            $isUnlocked = $item->is_lock == 0;
            
            if (!$isUnverified || !$isUnlocked) {
                \Log::warning("Filtering out verified/locked item", [
                    'id' => $item->id,
                    'assy' => $item->assy,
                    'verified_at' => $item->verified_at,
                    'is_lock' => $item->is_lock
                ]);
            }
            
            return $isUnverified && $isUnlocked;
        });

        // Group by cutoff while maintaining shift order
        // Items are already sorted by shift -> cutoff -> assy from query
        $grouped = $schedules->groupBy('cutoff')->map(function($items, $cutoff) {
            return [
                'cutoff' => $cutoff,
                'items' => $items->sortBy([
                    ['shift', 'asc'],
                    ['listing_id', 'asc']
                ])->map(function($item) {
                    return [
                        'id' => $item->id,
                        'assy' => $item->assy,
                        'qty' => $item->qty,
                        'shift' => $item->shift,
                        'listing_id' => $item->listing_id,
                        'assycode' => $item->assycode,
                        'seq' => $item->seq,
                        'plt' => $item->plt,
                        'mode' => $item->mode,
                        'snp' => $item->snp,
                        'snpa' => $item->snpa,
                        'verified_at' => $item->verified_at,
                        'is_lock' => $item->is_lock,
                    ];
                })->values()
            ];
        })->sortBy(function($group) {
            // Sort groups by the minimum shift number in each cutoff group
            return $group['items']->min('shift') ?? 99;
        })->values();

        \Log::info("Final result", [
            'grouped_count' => $grouped->count(),
            'total_items' => $grouped->sum(function($g) { return count($g['items']); })
        ]);

        return [
            'success' => true,
            'data' => $grouped
        ];
    }

    /**
     * Save verification changes
     */
    public function saveVerification($conveyorId, $date, $shift, $schedules = [], $newItems = [])
    {
        try {
            DB::beginTransaction();

            // Update existing schedules
            if (!empty($schedules)) {
                $this->updateExistingSchedules($schedules);
            }

            // Create new items (dragged from available)
            if (!empty($newItems)) {
                $this->createNewSchedules($conveyorId, $date, $shift, $newItems);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Schedule updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to save changes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing schedules
     */
    private function updateExistingSchedules($schedules)
    {
        foreach ($schedules as $scheduleData) {
            AssySchedule::where('id', $scheduleData['id'])
                ->update([
                    'cutoff' => $scheduleData['cutoff'],
                    'qty' => $scheduleData['qty'],
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Create new schedules from dragged items
     */
    private function createNewSchedules($conveyorId, $date, $shift, $newItems)
    {
        foreach ($newItems as $item) {
            $listingId = null;
            $assyCode = null;
            $seq = 0;
            $plt = 0;
            $mode = 0;
            $snp = 0;
            $snpa = 0;
            $transferredFromDate    = null;
            $transferredFromShift   = null;
            $transferredFromCutoff  = null;
            $transferredFromListing = null;
            
            // Check if this item has source_id (dragged from available)
            if (isset($item['source_id'])) {
                // Find and update the original item by deducting the quantity
                $sourceItem = AssySchedule::find($item['source_id']);

                if ($sourceItem) {
                    // Copy fields from source item
                    $listingId = $sourceItem->listing_id;
                    $assyCode = $sourceItem->assycode;
                    $seq = $sourceItem->seq ?? 0;
                    $plt = $sourceItem->plt ?? 0;
                    $mode = $sourceItem->mode ?? 0;
                    $snp = $sourceItem->snp ?? 0;
                    $snpa = $sourceItem->snpa ?? 0;

                    $transferredFromDate = $sourceItem->transferred_from_date
                        ? Carbon::parse($sourceItem->transferred_from_date)->format('Y-m-d')
                        : Carbon::parse($sourceItem->schedule)->format('Y-m-d');
                    $transferredFromShift   = $sourceItem->transferred_from_shift  ?? $sourceItem->shift;
                    $transferredFromCutoff  = $sourceItem->transferred_from_cutoff ?? $sourceItem->cutoff;
                    $transferredFromListing = $sourceItem->transferred_from_listing_id ?? $sourceItem->listing_id;
                    
                    // Deduct quantity from source
                    $this->deductSourceQuantity($sourceItem, $item['qty']);
                }
            }

            // Create new schedule item in current date/shift
            AssySchedule::create([
                'schedule' => $date,
                'conveyor_id' => $conveyorId,
                'listing_id' => $listingId ?? 0,
                'shift' => $shift,
                'assycode' => $assyCode ?? '',
                'cutoff' => $item['cutoff'],
                'assy' => $item['assy'],
                'qty' => $item['qty'],
                'seq' => $seq,
                'plt' => $plt,
                'mode' => $mode,
                'snp' => $snp,
                'snpa' => $snpa,
                'transferred_from_date'       => $transferredFromDate,
                'transferred_from_shift'      => $transferredFromShift,
                'transferred_from_cutoff'     => $transferredFromCutoff,
                'transferred_from_listing_id' => $transferredFromListing,
                'is_lock' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Deduct quantity from source item or delete if no quantity remains
     */
    private function deductSourceQuantity($sourceItem, $deductQty)
    {
        $newSourceQty = $sourceItem->qty - $deductQty;
        
        if ($newSourceQty > 0) {
            // Update remaining quantity in source
            $sourceItem->qty = $newSourceQty;
            $sourceItem->updated_by = Auth::id();
            $sourceItem->save();
        } else {
            // Delete only if no quantity remains
            $sourceItem->delete();
        }
    }

    /**
     * Verify schedule - save changes and lock the schedule for specific conveyor, date, and shift
     */
    public function verifySchedule($conveyorId, $date, $shift, $cutoffs = [])
    {
        try {
            DB::beginTransaction();

            $date = Carbon::parse($date);

            Log::info("verifySchedule called", [
                'conveyor_id' => $conveyorId,
                'date' => $date->format('Y-m-d'),
                'shift' => $shift,
                'cutoffs_count' => count($cutoffs),
                'cutoffs' => $cutoffs
            ]);

            // Step 1: Save any pending changes if cutoffs data is provided
            if (!empty($cutoffs)) {
                // Get existing schedules keyed by ID to preserve original data
                $existingSchedules = AssySchedule::where('conveyor_id', $conveyorId)
                    ->whereDate('schedule', $date)
                    ->where('shift', $shift)
                    ->get()
                    ->keyBy('id');

                Log::info("Existing schedules found", [
                    'count' => $existingSchedules->count(),
                    'ids' => $existingSchedules->keys()->toArray()
                ]);

                // Delete existing schedules for this shift
                if ($existingSchedules->isNotEmpty()) {
                    AssySchedule::whereIn('id', $existingSchedules->keys()->toArray())->delete();
                }

                // Recreate schedules based on cutoffs data
                foreach ($cutoffs as $cutoffData) {
                    $cutoffNumber = (int) ($cutoffData['cutoff'] ?? 0);
                    
                    Log::info("Processing cutoff", [
                        'cutoff' => $cutoffNumber,
                        'items_count' => count($cutoffData['items'] ?? [])
                    ]);
                    
                    if (!empty($cutoffData['items'])) {
                        foreach ($cutoffData['items'] as $item) {
                            // Handle items dragged from available
                            $type = $item['type'] ?? 'current';
                            $itemIdRaw = $item['id'] ?? 0;
                            $itemId = is_numeric($itemIdRaw) ? (int) $itemIdRaw : 0;
                            $sourceId = isset($item['source_id']) && !empty($item['source_id']) ? (int) $item['source_id'] : null;
                            
                            // Check if this is a new item from available (has source_id OR id starts with "new_")
                            $isFromAvailable = ($type === 'available') || 
                                               ($sourceId !== null) || 
                                               (is_string($itemIdRaw) && strpos($itemIdRaw, 'new_') === 0);
                            
                            Log::info("Processing item", [
                                'itemIdRaw' => $itemIdRaw,
                                'itemId' => $itemId,
                                'type' => $type,
                                'source_id' => $sourceId,
                                'isFromAvailable' => $isFromAvailable,
                                'qty' => $item['qty'] ?? 0,
                                'assy' => $item['assy'] ?? '',
                                'found_in_existing' => $existingSchedules->has($itemId)
                            ]);
                            
                            if ($isFromAvailable && $sourceId) {
                                // Get source item to copy data from
                                $sourceItem = AssySchedule::find($sourceId);
                                
                                if ($sourceItem) {
                                    Log::info("Creating schedule from source", ['source_id' => $sourceId]);
                                    
                                    // Capture source position BEFORE deduction.
                                    // If source item was itself transferred from another place,
                                    // propagate that original origin so the trail is preserved.
                                    $originDate    = $sourceItem->transferred_from_date
                                        ? Carbon::parse($sourceItem->transferred_from_date)->format('Y-m-d')
                                        : Carbon::parse($sourceItem->schedule)->format('Y-m-d');
                                    $originShift   = $sourceItem->transferred_from_shift  ?? $sourceItem->shift;
                                    $originCutoff  = $sourceItem->transferred_from_cutoff ?? $sourceItem->cutoff;
                                    $originListing = $sourceItem->transferred_from_listing_id ?? $sourceItem->listing_id;

                                    // Create new schedule from source
                                    AssySchedule::create([
                                        'schedule' => $date,
                                        'conveyor_id' => $conveyorId,
                                        'listing_id' => $sourceItem->listing_id,
                                        'shift' => $shift,
                                        'assycode' => $sourceItem->assycode,
                                        'assy' => $sourceItem->assy,
                                        'qty' => $item['qty'],
                                        'cutoff' => $cutoffNumber,
                                        'seq' => $sourceItem->seq ?? 0,
                                        'plt' => $sourceItem->plt ?? 0,
                                        'mode' => $sourceItem->mode ?? 0,
                                        'snp' => $sourceItem->snp ?? 0,
                                        'snpa' => $sourceItem->snpa ?? 0,
                                        'transferred_from_date'       => $originDate,
                                        'transferred_from_shift'      => $originShift,
                                        'transferred_from_cutoff'     => $originCutoff,
                                        'transferred_from_listing_id' => $originListing,
                                        'created_by' => Auth::id(),
                                        'updated_by' => Auth::id(),
                                    ]);
                                    
                                    // Deduct quantity from source or delete if depleted
                                    $this->deductSourceQuantity($sourceItem, $item['qty']);
                                } else {
                                    Log::warning("Source item not found", ['source_id' => $sourceId]);
                                }
                            } else {
                                // Regular item - use original schedule data if available
                                $originalSchedule = $existingSchedules->get($itemId);
                                
                                if ($originalSchedule) {
                                    // Use data from original schedule
                                    AssySchedule::create([
                                        'schedule' => $date,
                                        'conveyor_id' => $conveyorId,
                                        'listing_id' => $originalSchedule->listing_id,
                                        'shift' => $shift,
                                        'assycode' => $originalSchedule->assycode,
                                        'assy' => $originalSchedule->assy,
                                        'qty' => $item['qty'], // Use possibly modified qty
                                        'cutoff' => $cutoffNumber,
                                        'seq' => $originalSchedule->seq ?? 0,
                                        'plt' => $originalSchedule->plt ?? 0,
                                        'mode' => $originalSchedule->mode ?? 0,
                                        'snp' => $originalSchedule->snp ?? 0,
                                        'snpa' => $originalSchedule->snpa ?? 0,
                                        'created_by' => Auth::id(),
                                        'updated_by' => Auth::id(),
                                    ]);
                                    Log::info("Created schedule from original", ['itemId' => $itemId]);
                                } else {
                                    // Fallback: try to find by listing_id
                                    $listingId = (int) ($item['listing_id'] ?? 0);
                                    $listingStage = $listingId ? \App\Models\ListingStage::find($listingId) : null;
                                    
                                    Log::info("Fallback to listing_stage", [
                                        'listing_id' => $listingId,
                                        'found' => $listingStage ? true : false
                                    ]);
                                    
                                    if ($listingStage) {
                                        AssySchedule::create([
                                            'schedule' => $date,
                                            'conveyor_id' => $conveyorId,
                                            'listing_id' => $listingStage->id,
                                            'shift' => $shift,
                                            'assycode' => $listingStage->assycode,
                                            'assy' => $listingStage->assy,
                                            'qty' => $item['qty'],
                                            'cutoff' => $cutoffNumber,
                                            'seq' => $listingStage->seq ?? 0,
                                            'plt' => $listingStage->plt ?? 0,
                                            'mode' => $listingStage->mode ?? 0,
                                            'snp' => $listingStage->snp ?? 0,
                                            'snpa' => $listingStage->snpa ?? 0,
                                            'created_by' => Auth::id(),
                                            'updated_by' => Auth::id(),
                                        ]);
                                        Log::info("Created schedule from listing_stage", ['listing_id' => $listingId]);
                                    } else {
                                        Log::warning("Skipping item - no valid data found", ['item' => $item]);
                                    }
                                    // Skip items without valid data (same as saveManageData behavior)
                                }
                            }
                        }
                    }
                }
            }

            // Step 2: Lock all schedules for this conveyor, date, and shift
            $affected = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->where('shift', $shift)
                ->update([
                    'is_lock' => 1,
                    'verified_at' => now(),
                    'verified_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);

            // Step 3: Generate kanban data for circuits and shikakes
            $kanbanResult = $this->kanbanGenerator->generateKanbanForSchedule(
                $conveyorId,
                $date->format('Y-m-d'),
                $shift
            );

            if (!$kanbanResult['success']) {
                // Rollback entire transaction if kanban generation fails
                // Schedule should NOT be locked without corresponding kanbans
                throw new \RuntimeException('Kanban generation failed: ' . ($kanbanResult['message'] ?? 'Unknown error'));
            }

            DB::commit();

            $message = "Schedule verified successfully. {$affected} records locked.";
            if ($kanbanResult['success']) {
                $message .= " Generated {$kanbanResult['circuit_count']} circuit and {$kanbanResult['shikake_count']} shikake kanbans.";
            }

            return [
                'success' => true,
                'message' => $message,
                'affected' => $affected,
                'kanban_data' => $kanbanResult
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to verify schedule: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Preview unverify side effects: which transferred items can be restored to their origin,
     * and which ones will be lost because the origin schedule has already been verified.
     */
    public function previewUnverify($conveyorId, $date, $shift)
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        $transferredItems = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->whereNotNull('transferred_from_date')
            ->get();

        $restorable = [];
        $lost       = [];

        foreach ($transferredItems as $item) {
            $originDate  = Carbon::parse($item->transferred_from_date)->format('Y-m-d');
            $originShift = (int) $item->transferred_from_shift;

            $isOriginVerified = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $originDate)
                ->where('shift', $originShift)
                ->where('is_lock', 1)
                ->exists();

            $info = [
                'assy'          => $item->assy,
                'assycode'      => $item->assycode,
                'qty'           => $item->qty,
                'origin_date'   => $originDate,
                'origin_shift'  => $originShift,
                'origin_cutoff' => (int) $item->transferred_from_cutoff,
            ];

            if ($isOriginVerified) {
                $lost[] = $info;
            } else {
                $restorable[] = $info;
            }
        }

        return [
            'success'    => true,
            'restorable' => $restorable,
            'lost'       => $lost,
            'has_warning'=> count($lost) > 0,
            'has_transfer' => count($restorable) + count($lost) > 0,
        ];
    }

    /**
     * Restore transferred items back to their origin schedule group.
     * If origin is still unverified, qty is merged into an existing origin record
     * or a new record is recreated. If origin has been verified, the item is lost
     * (skipped). Must be called BEFORE deleting the current schedule group records.
     */
    private function restoreTransferredItemsToOrigin($conveyorId, $dateStr, $shift)
    {
        $transferredItems = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $dateStr)
            ->where('shift', $shift)
            ->whereNotNull('transferred_from_date')
            ->get();

        $restoredCount = 0;
        $lostCount     = 0;

        foreach ($transferredItems as $item) {
            $originDate    = Carbon::parse($item->transferred_from_date)->format('Y-m-d');
            $originShift   = (int) $item->transferred_from_shift;
            $originCutoff  = (int) ($item->transferred_from_cutoff ?? 0);
            $originListing = (int) ($item->transferred_from_listing_id ?? $item->listing_id);

            $isOriginVerified = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $originDate)
                ->where('shift', $originShift)
                ->where('is_lock', 1)
                ->exists();

            if ($isOriginVerified) {
                $lostCount++;
                Log::info('Unverify: origin already verified, item lost', [
                    'conveyor_id' => $conveyorId,
                    'assy'        => $item->assy,
                    'qty'         => $item->qty,
                    'origin'      => "$originDate shift $originShift CO$originCutoff",
                ]);
                continue;
            }

            // Merge into existing origin record with same listing/cutoff if present
            $existing = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $originDate)
                ->where('shift', $originShift)
                ->where('cutoff', $originCutoff)
                ->where('listing_id', $originListing)
                ->where('is_lock', 0)
                ->first();

            if ($existing) {
                $existing->qty = (int) $existing->qty + (int) $item->qty;
                $existing->updated_by = Auth::id();
                $existing->save();
            } else {
                AssySchedule::create([
                    'schedule'    => $originDate,
                    'conveyor_id' => $conveyorId,
                    'listing_id'  => $originListing,
                    'shift'       => $originShift,
                    'assycode'    => $item->assycode,
                    'assy'        => $item->assy,
                    'qty'         => $item->qty,
                    'cutoff'      => $originCutoff,
                    'seq'         => $item->seq ?? 0,
                    'plt'         => $item->plt ?? 0,
                    'mode'        => $item->mode ?? 0,
                    'snp'         => $item->snp ?? 0,
                    'snpa'        => $item->snpa ?? 0,
                    'is_lock'     => 0,
                    'created_by'  => Auth::id(),
                    'updated_by'  => Auth::id(),
                ]);
            }

            $restoredCount++;
        }

        return [
            'restored_count' => $restoredCount,
            'lost_count'     => $lostCount,
        ];
    }

    /**
     * Unverify schedule - unlock the schedule for specific conveyor, date, and shift.
     * Reverses balance, clears kanbans, then regenerates schedules from listing_stage
     * to restore the pre-verification state.
     */
    public function unverifySchedule($conveyorId, $date, $shift)
    {
        try {
            DB::beginTransaction();

            $date = Carbon::parse($date);
            $dateStr = $date->format('Y-m-d');

            // Step 0: Restore transferred items back to their origin (if origin still unverified)
            $restoreResult = $this->restoreTransferredItemsToOrigin($conveyorId, $dateStr, $shift);

            // Step 1: Reverse balance contributions from existing kanbans BEFORE clearing
            $this->kanbanGenerator->reverseBalanceForScheduleGroup($conveyorId, $dateStr, $shift);

            // Step 2: Clear generated kanbans for this schedule group
            $this->kanbanGenerator->clearKanbanData($conveyorId, $dateStr, $shift);

            // Step 3: Delete current assy_schedule records for this conveyor/date/shift
            $deletedCount = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $dateStr)
                ->where('shift', $shift)
                ->delete();

            Log::info("unverifySchedule: Deleted schedules", [
                'conveyor_id' => $conveyorId,
                'date' => $dateStr,
                'shift' => $shift,
                'deleted' => $deletedCount,
            ]);

            // Step 4: Regenerate from listing_stage (restore original allocation)
            $conveyor = MasterConveyor::find($conveyorId);
            $regeneratedCount = 0;

            if ($conveyor) {
                $listings = ListingStage::where('conveyor', $conveyor->conveyor)
                    ->whereDate('listing_date_time', $dateStr)
                    ->where('shift', $shift)
                    ->whereNotNull('assycode')
                    ->where('assycode', '!=', '')
                    ->whereNotNull('assy')
                    ->where('assy', '!=', '')
                    ->where('qty', '>', 0)
                    ->orderBy('id_listing', 'asc')
                    ->orderBy('seq', 'asc')
                    ->get();

                if ($listings->isNotEmpty()) {
                    // Initialize rem_qty tracking
                    $this->listingAllocator->initializeListings($listings);

                    // Get shift lock status (this shift won't be locked since we just deleted)
                    $shiftLockStatus = $this->lockChecker->getShiftLockStatus($date, $conveyor->id);
                    // Force this shift as unlocked
                    $shiftLockStatus[$shift] = false;

                    // Calculate cutoff capacities
                    $shiftCapacities = $this->capacityCalculator->calculateShiftCapacities(
                        $conveyor,
                        $shiftLockStatus
                    );

                    if (isset($shiftCapacities[$shift])) {
                        // Pre-map CO5
                        $totalQty = $listings->sum('rem_qty');
                        $co5Needed = $this->capacityCalculator->preMapCutoff5(
                            $shiftCapacities, $conveyor->capacity, $totalQty
                        );

                        // Allocate to shift
                        $allocationResult = $this->listingAllocator->allocateToShift(
                            $listings,
                            $shiftCapacities[$shift],
                            $shift,
                            $conveyor->id,
                            $dateStr
                        );

                        // Bulk insert
                        if (!empty($allocationResult['schedules'])) {
                            foreach (array_chunk($allocationResult['schedules'], 500) as $chunk) {
                                AssySchedule::insert($chunk);
                            }
                            $regeneratedCount = count($allocationResult['schedules']);
                        }
                    }

                    Log::info("unverifySchedule: Regenerated from listing_stage", [
                        'listings_found' => $listings->count(),
                        'schedules_created' => $regeneratedCount,
                    ]);
                }
            }

            DB::commit();

            $message = "Schedule berhasil di-unverify. {$deletedCount} record dihapus, {$regeneratedCount} record di-regenerate dari data listing asli.";
            if (!empty($restoreResult['restored_count'])) {
                $message .= " {$restoreResult['restored_count']} item transfer dikembalikan ke jadwal asal.";
            }
            if (!empty($restoreResult['lost_count'])) {
                $message .= " {$restoreResult['lost_count']} item transfer HILANG karena jadwal asal sudah diverifikasi.";
            }

            return [
                'success' => true,
                'message' => $message,
                'affected' => $regeneratedCount,
                'restored_count' => $restoreResult['restored_count'] ?? 0,
                'lost_count'     => $restoreResult['lost_count'] ?? 0,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("unverifySchedule failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'message' => 'Failed to unverify schedule: ' . $e->getMessage()
            ];
        }
    }
}
