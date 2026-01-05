<?php

namespace App\Services;

use App\Models\AssySchedule;
use App\Models\MasterConveyor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleVerificationService
{
    /**
     * Get datatable query for schedule verification
     */
    public function getDatatableQuery($startDate = null, $endDate = null, $conveyorId = null, $status = null)
    {
        $query = AssySchedule::with('conveyor')
            ->select(
                'conveyor_id',
                DB::raw('DATE(schedule) as schedule_date'),
                'shift',
                DB::raw('GROUP_CONCAT(DISTINCT assy ORDER BY assy SEPARATOR ", ") as assy_list'),
                DB::raw('SUM(qty) as total_listing'),
                DB::raw('MAX(is_lock) as is_lock'),
                DB::raw('MIN(id) as first_id')
            )
            ->groupBy('conveyor_id', 'schedule_date', 'shift');

        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween('schedule', [$startDate, $endDate]);
        }

        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }

        // Filter by status
        if ($status === 'verified') {
            $query->having('is_lock', '=', 1);
        } elseif ($status === 'pending') {
            $query->having('is_lock', '=', 0);
        }

        $query->orderBy('schedule_date', 'asc')
              ->orderBy('conveyor_id', 'asc')
              ->orderBy('shift', 'asc');

        return $query->get();
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
            ->orderBy('seq', 'asc')
            ->get();

        if ($schedules->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No schedules found'
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
        
        // Calculate Cut Off 5 capacity (0.875 x capacity per normal CO)
        $normalCutOffCapacity = $conveyor->capacity / 4;
        $cutOff5Capacity = round($normalCutOffCapacity * 0.875, 2);

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
     */
    public function getAvailableAssyData($conveyorId, $date, $shift)
    {
        // Get schedules for specific date and shift, grouped by cut-off
        $schedules = AssySchedule::where('conveyor_id', $conveyorId)
            ->where('schedule', $date)
            ->where('shift', $shift)
            ->where('is_lock', 0)
            ->select('id', 'assy', 'qty', 'cutoff', 'listing_id', 'assycode', 'seq', 'plt', 'mode', 'snp', 'snpa')
            ->orderBy('cutoff')
            ->orderBy('assy')
            ->get();

        // Group by cut-off
        $grouped = $schedules->groupBy('cutoff')->map(function($items, $cutoff) {
            return [
                'cutoff' => $cutoff,
                'items' => $items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'assy' => $item->assy,
                        'qty' => $item->qty,
                        'listing_id' => $item->listing_id,
                        'assycode' => $item->assycode,
                        'seq' => $item->seq,
                        'plt' => $item->plt,
                        'mode' => $item->mode,
                        'snp' => $item->snp,
                        'snpa' => $item->snpa,
                    ];
                })->values()
            ];
        })->values();

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

            DB::commit();

            return [
                'success' => true,
                'message' => "Schedule verified successfully. {$affected} records locked.",
                'affected' => $affected
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
     * Unverify schedule - unlock the schedule for specific conveyor, date, and shift
     */
    public function unverifySchedule($conveyorId, $date, $shift)
    {
        try {
            DB::beginTransaction();

            // Update all schedules for this conveyor, date, and shift
            $affected = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->where('shift', $shift)
                ->where('is_lock', 1) // Only unverify locked schedules
                ->update([
                    'is_lock' => 0,
                    'verified_at' => null,
                    'verified_by' => null,
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Schedule unverified successfully. {$affected} records unlocked.",
                'affected' => $affected
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to unverify schedule: ' . $e->getMessage()
            ];
        }
    }
}
