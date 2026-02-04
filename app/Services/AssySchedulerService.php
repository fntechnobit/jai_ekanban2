<?php

namespace App\Services;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use App\Services\Schedule\ShiftCapacityCalculator;
use App\Services\Schedule\ShiftLockChecker;
use App\Services\Schedule\ListingAllocator;
use App\Services\Schedule\ScheduleCleanupService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssySchedulerService
{
    protected $listingSyncService;
    protected $capacityCalculator;
    protected $lockChecker;
    protected $listingAllocator;
    protected $scheduleCleanup;

    public function __construct(
        ListingSyncService $listingSyncService,
        ShiftCapacityCalculator $capacityCalculator,
        ShiftLockChecker $lockChecker,
        ListingAllocator $listingAllocator,
        ScheduleCleanupService $scheduleCleanup
    ) {
        $this->listingSyncService = $listingSyncService;
        $this->capacityCalculator = $capacityCalculator;
        $this->lockChecker = $lockChecker;
        $this->listingAllocator = $listingAllocator;
        $this->scheduleCleanup = $scheduleCleanup;
    }
    /**
     * Generate assy schedules for the specified date range with cutoff system
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $conveyorId
     * @return array
     */
    public function generateSchedules($startDate, $endDate, $conveyorId = null)
    {
        try {
            DB::beginTransaction();

            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            // Step 1: Delete existing listing_stage data for the date range
            $deleteListingResult = $this->listingSyncService->deleteListingStageData(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );
            
            if (!$deleteListingResult['success']) {
                throw new \Exception('Failed to clean listing_stage data: ' . $deleteListingResult['message']);
            }

            // Step 2: Sync fresh listing data from mysql_listing
            $syncResult = $this->listingSyncService->syncListingData(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );
            
            if (!$syncResult['success']) {
                throw new \Exception('Failed to sync listing data: ' . $syncResult['message']);
            }

            // Step 3: Get fresh listing data from listing_stage for the date range
            // Skip listings that already have:
            // - locked/verified schedules (is_lock != 0)
            // - user-edited schedules (is_user_edited = 1) 
            // - soft-deleted schedules (deleted_at IS NOT NULL)
            // Note: We use raw query to bypass SoftDeletes trait and check ALL records including soft-deleted
            $listingsQuery = ListingStage::whereBetween('listing_date_time', [$startDate, $endDate])
                ->whereNotNull('assycode')
                ->where('assycode', '!=', '')
                ->whereNotNull('assy')
                ->where('assy', '!=', '')
                ->where('qty', '>', 0)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('assy_schedule')
                        ->whereColumn('assy_schedule.schedule', DB::raw('DATE(listing_stage.listing_date_time)'))
                        ->whereColumn('assy_schedule.assycode', 'listing_stage.assycode')
                        ->whereColumn('assy_schedule.assy', 'listing_stage.assy')
                        ->whereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('master_conveyor')
                                ->whereColumn('master_conveyor.id', 'assy_schedule.conveyor_id')
                                ->whereColumn('master_conveyor.conveyor', 'listing_stage.conveyor');
                        })
                        // Skip listing if matching schedule is: locked OR user-edited OR soft-deleted
                        ->where(function ($q) {
                            $q->where('assy_schedule.is_lock', '!=', 0)
                              ->orWhere('assy_schedule.is_user_edited', '=', 1)
                              ->orWhereNotNull('assy_schedule.deleted_at');
                        });
                })
                ->orderBy('id', 'asc')
                ->orderBy('listing_date_time', 'asc')
                ->orderBy('seq', 'asc')
                ->orderBy('assycode', 'asc');

            if ($conveyorId) {
                $listingsQuery->where('conveyor', function($query) use ($conveyorId) {
                    $query->select('conveyor')
                        ->from('master_conveyor')
                        ->where('id', $conveyorId)
                        ->limit(1);
                });
            }

            $listings = $listingsQuery->get();

            if ($listings->isEmpty()) {
                DB::commit();
                return [
                    'success' => true,
                    'message' => 'No listings found for the specified date range',
                    'generated' => 0,
                ];
            }

            // Group listings by date and conveyor
            $groupedListings = $listings->groupBy(function ($listing) {
                return $listing->listing_date_time->format('Y-m-d') . '_' . $listing->conveyor;
            });

            $generatedCount = 0;
            $schedulesToCreate = [];

            foreach ($groupedListings as $groupKey => $groupListings) {
                list($date, $conveyorName) = explode('_', $groupKey, 2);
                
                // Get conveyor from master_conveyor
                $conveyor = MasterConveyor::where('conveyor', $conveyorName)->first();
                
                // If conveyor not found, skip this group
                if (!$conveyor) {
                    Log::warning("Conveyor not found: {$conveyorName}");
                    continue;
                }

                $scheduleDate = Carbon::parse($date);
                $shiftCapacity = $conveyor->capacity ?? 100;
                $maxShifts = $conveyor->shift_qty ?? 2;

                // Step 4: Initialize tracking field for listings (rem_qty)
                $this->listingAllocator->initializeListings($groupListings);

                // Step 5: Check shift lock status for this conveyor on this date
                $shiftLockStatus = $this->lockChecker->getShiftLockStatus(
                    $scheduleDate,
                    $conveyor->id,
                    $maxShifts
                );

                // Step 6: Delete only unlocked schedules (preserves locked, user-edited, and soft-deleted)
                $this->scheduleCleanup->deleteUnlockedSchedulesInRange(
                    $scheduleDate,
                    $scheduleDate,
                    $conveyor->id
                );

                // Step 7: Calculate cutoff capacities for each shift
                $shiftCapacities = $this->capacityCalculator->calculateShiftCapacities(
                    $conveyor,
                    $shiftLockStatus
                );

                // Step 8: Process shifts SEQUENTIALLY (S1 first, then S2) - matching SP logic
                // Process Shift 1 cutoffs 1-4 first
                if (!($shiftLockStatus[1] ?? false) && isset($shiftCapacities[1])) {
                    $allocationResult = $this->listingAllocator->allocateToShift(
                        $groupListings,
                        $shiftCapacities[1],
                        1,
                        $conveyor->id,
                        $scheduleDate->format('Y-m-d')
                    );
                    $schedulesToCreate = array_merge($schedulesToCreate, $allocationResult['schedules']);
                }

                // Check if there's remaining quantity before processing Shift 2
                $remainingQty = $groupListings->sum('rem_qty');
                if ($remainingQty === 0) {
                    // No remaining listings, skip to next conveyor group
                    continue;
                }

                // Process Shift 2 cutoffs 1-4 (only if maxShifts >= 2)
                if ($maxShifts >= 2 && !($shiftLockStatus[2] ?? false) && isset($shiftCapacities[2])) {
                    $allocationResult = $this->listingAllocator->allocateToShift(
                        $groupListings,
                        $shiftCapacities[2],
                        2,
                        $conveyor->id,
                        $scheduleDate->format('Y-m-d')
                    );
                    $schedulesToCreate = array_merge($schedulesToCreate, $allocationResult['schedules']);
                }

                // Step 9: Handle overflow (cutoff 5) for remaining quantities
                // Using SP formula: S1 cap = FLOOR(0.875 * (capacity / 4))
                $overflowResult = $this->listingAllocator->allocateOverflow(
                    $groupListings,
                    $shiftLockStatus,
                    $maxShifts,
                    $conveyor->shift_start ?? 1,
                    $shiftCapacity,
                    $conveyor->id,
                    $scheduleDate->format('Y-m-d')
                );

                $schedulesToCreate = array_merge($schedulesToCreate, $overflowResult['schedules']);
            }

            // Step 10: Bulk insert all schedules
            if (!empty($schedulesToCreate)) {
                foreach (array_chunk($schedulesToCreate, 500) as $chunk) {
                    AssySchedule::insert($chunk);
                }
                $generatedCount = count($schedulesToCreate);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Generated {$generatedCount} schedule(s) successfully",
                'generated' => $generatedCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Schedule generation failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'message' => 'Failed to generate schedules: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get schedules for datatable
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getSchedulesQuery($filters = [])
    {
        $query = AssySchedule::with(['conveyor', 'listingStage'])
            ->orderBy('schedule', 'asc')
            ->orderBy('conveyor_id', 'asc')
            ->orderBy('shift', 'asc');

        // Apply filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('schedule', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('schedule', '<=', $filters['end_date']);
        }

        if (!empty($filters['conveyor_id'])) {
            $query->where('conveyor_id', $filters['conveyor_id']);
        }

        return $query;
    }

    /**
     * Verify a schedule (change status)
     *
     * @param int $scheduleId
     * @return array
     */
    public function verifySchedule($scheduleId)
    {
        try {
            // TODO: Implement verification logic
            $schedule = AssySchedule::findOrFail($scheduleId);
            
            // Update status or is_lock field
            $schedule->is_lock = 1;
            $schedule->updated_by = Auth::id();
            $schedule->save();

            return [
                'success' => true,
                'message' => 'Schedule verified successfully',
            ];
        } catch (\Exception $e) {
            Log::error("Schedule verification failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to verify schedule: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete schedule(s) for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $conveyorId
     * @return array
     */
    public function deleteSchedules($startDate, $endDate, $conveyorId = null)
    {
        try {
            $query = AssySchedule::whereDate('schedule', '>=', $startDate)
                ->whereDate('schedule', '<=', $endDate);

            if ($conveyorId) {
                $query->where('conveyor_id', $conveyorId);
            }

            $deletedCount = $query->delete();

            return [
                'success' => true,
                'message' => "Deleted {$deletedCount} schedule(s) successfully",
                'deleted' => $deletedCount,
            ];
        } catch (\Exception $e) {
            Log::error("Schedule deletion failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to delete schedules: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete existing assy_schedule records for the specified date range
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param int|null $conveyorId
     * @return int Number of deleted records
     */
    private function deleteExistingSchedules($startDate, $endDate, $conveyorId = null)
    {
        $query = AssySchedule::whereBetween('schedule', [$startDate, $endDate]);
        
        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }
        
        $deletedCount = $query->delete();
        
        Log::info("Deleted {$deletedCount} existing assy_schedule records for date range", [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'conveyor_id' => $conveyorId
        ]);
        
        return $deletedCount;
    }

    /**
     * Get manage data for a specific conveyor and date
     *
     * @param int $conveyorId
     * @param string $date
     * @return array
     */
    public function getManageData($conveyorId, $date)
    {
        try {
            $date = Carbon::parse($date);
            $conveyor = MasterConveyor::findOrFail($conveyorId);

            // Get existing scheduled items
            $scheduledItems = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->with('listingStage')
                ->orderBy('shift')
                ->orderBy('seq')
                ->get();

            // Group scheduled items by shift
            $shifts = [];
            $maxShifts = $conveyor->shift_qty ?? 3;
            $shiftCapacity = $conveyor->capacity ?? 100;

            // Initialize all shifts
            for ($i = 1; $i <= $maxShifts; $i++) {
                $shifts[$i] = [
                    'total_capacity' => $shiftCapacity,
                    'used_capacity' => 0,
                    'items' => []
                ];
            }

            // Populate shifts with scheduled items
            foreach ($scheduledItems as $item) {
                if (isset($shifts[$item->shift])) {
                    $shifts[$item->shift]['items'][] = [
                        'id' => $item->id,
                        'assy' => $item->assy,
                        'qty' => $item->qty,
                        'listing_id' => $item->listing_id,
                        'listing_date_time' => $item->listingStage ? $item->listingStage->listing_date_time->format('Y-m-d H:i') : ''
                    ];
                    $shifts[$item->shift]['used_capacity'] += $item->qty;
                }
            }

            // Calculate real counts for header
            $totalAssyCount = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->distinct('assy')
                ->count();
                
            $totalListingCount = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereDate('schedule', $date)
                ->sum('qty');

            return [
                'success' => true,
                'shifts' => $shifts,
                'conveyor' => $conveyor,
                'date' => $date->format('Y-m-d'),
                'total_assy_count' => $totalAssyCount,
                'total_listing_count' => $totalListingCount
            ];
        } catch (\Exception $e) {
            Log::error("Get manage data failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to get manage data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Save manage data changes
     *
     * @param int $conveyorId
     * @param string $date
     * @param array $shifts
     * @return array
     */
    public function saveManageData($conveyorId, $date, $shifts)
    {
        try {
            DB::beginTransaction();

            $date = Carbon::parse($date);
            $conveyor = MasterConveyor::findOrFail($conveyorId);

            // Collect all item IDs that are being updated
            $itemsToUpdateIds = [];
            foreach ($shifts as $shiftNumber => $shiftData) {
                if (!empty($shiftData['items'])) {
                    foreach ($shiftData['items'] as $item) {
                        $itemsToUpdateIds[] = $item['id'];
                    }
                }
            }

            // Only delete existing schedules that are being replaced/updated
            if (!empty($itemsToUpdateIds)) {
                AssySchedule::whereIn('id', $itemsToUpdateIds)->delete();
            }

            $createdCount = 0;

            // Recreate schedules based on new arrangement
            foreach ($shifts as $shiftNumber => $shiftData) {
                if (!empty($shiftData['items'])) {
                    foreach ($shiftData['items'] as $item) {
                        // Get original listing data
                        $type = $item['type'] ?? 'shift'; // Default to 'shift' if not provided

                        if ($type === 'available') {
                            // delete assy schedule from original date
                            AssySchedule::where('id', $item['id'])->delete();
                        }

                        $listingStage = ListingStage::find($item['listing_id']);
                        
                        if ($listingStage) {
                            AssySchedule::create([
                                'schedule' => $date,
                                'conveyor_id' => $conveyorId,
                                'listing_id' => $listingStage->id,
                                'shift' => $shiftNumber,
                                'assycode' => $listingStage->assycode,
                                'assy' => $listingStage->assy,
                                'qty' => $item['qty'], // Use the possibly modified qty
                                'seq' => $listingStage->seq,
                                'mode' => $listingStage->mode,
                                'snp' => $listingStage->snp,
                                'snpa' => $listingStage->snpa,
                                'created_by' => Auth::id(),
                            ]);
                            $createdCount++;
                        }
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully updated schedule with {$createdCount} items",
                'created' => $createdCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Save manage data failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to save manage data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available assy data with date range filter and pagination
     *
     * @param int $conveyorId
     * @param string $selectedDate
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAvailableAssyData($conveyorId, $selectedDate, $startDate = null, $endDate = null, $page = 1, $perPage = 20)
    {
        try {
            $selectedDate = Carbon::parse($selectedDate);

            // Default date range: selected date to +7 days
            if (!$startDate) {
                $startDate = $selectedDate->format('Y-m-d');
            }
            if (!$endDate) {
                $endDate = $selectedDate->copy()->addDays(7)->format('Y-m-d');
            }

            // Validate maximum 7-day range
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            if ($start->diffInDays($end) > 7) {
                // Adjust to 7-day range from selected date
                $startDate = $selectedDate->format('Y-m-d');
                $endDate = $selectedDate->copy()->addDays(7)->format('Y-m-d');
            }

            // Get available assy schedules from future dates that can be moved to selected date
            $query = AssySchedule::where('conveyor_id', $conveyorId)
                ->whereBetween('schedule', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                // Only get schedules from dates after the selected date
                ->whereDate('schedule', '!=', $selectedDate)
                // Exclude assy codes already assigned to the selected date
                ->whereNotNull('assycode')
                ->where('assycode', '!=', '')
                ->whereNotNull('assy')
                ->where('assy', '!=', '')
                ->where('qty', '>', 0)
                ->orderBy('schedule')
                ->orderBy('shift')
                ->orderBy('seq');

            // Get paginated results
            $totalCount = $query->count();
            $items = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Prepare available items
            $available = [];
            foreach ($items as $item) {
                $available[] = [
                    'id' => $item->id,
                    'assy' => $item->assy,
                    'qty' => $item->qty,
                    'schedule_date' => $item->schedule->format('Y-m-d'),
                    'shift' => $item->shift,
                    'listing_id' => $item->listing_id
                ];
            }

            $totalPages = ceil($totalCount / $perPage);

            return [
                'success' => true,
                'available' => $available,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'selected_date' => $selectedDate->format('Y-m-d')
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Get available assy data failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to get available assy data: ' . $e->getMessage(),
            ];
        }
    }
}
