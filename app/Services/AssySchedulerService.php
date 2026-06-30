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
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();

        // ─── STEP 1: Clone listing data from mysql_listing → listing_stage ───
        try {
            DB::beginTransaction();

            $deleteListingResult = $this->listingSyncService->deleteListingStageData(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (!$deleteListingResult['success']) {
                DB::rollBack();
                return [
                    'success'     => false,
                    'step_failed' => 'sync_listing',
                    'message'     => 'Gagal membersihkan data listing_stage: ' . $deleteListingResult['message'],
                    'sync_detail' => null,
                    'generated'   => 0,
                ];
            }

            $syncResult = $this->listingSyncService->syncListingData(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (!$syncResult['success']) {
                DB::rollBack();
                return [
                    'success'     => false,
                    'step_failed' => 'sync_listing',
                    'message'     => 'Gagal mengambil data listing terbaru dari database listing: ' . ($syncResult['message'] ?? 'Koneksi database listing tidak tersedia.'),
                    'sync_detail' => null,
                    'generated'   => 0,
                ];
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Listing sync/clone failed", ['error' => $e->getMessage()]);
            return [
                'success'     => false,
                'step_failed' => 'sync_listing',
                'message'     => 'Tidak dapat terhubung ke database listing. Proses generate dihentikan. (' . $e->getMessage() . ')',
                'sync_detail' => null,
                'generated'   => 0,
            ];
        }

        $syncDetail = [
            'total_records' => $syncResult['total_records'] ?? 0,
            'synced'        => $syncResult['synced']        ?? 0,
            'skipped'       => $syncResult['skipped']       ?? 0,
        ];

        // ─── STEP 2: Generate assy schedules from listing_stage ──────────────
        try {
            DB::beginTransaction();

            // Get fresh listing data from listing_stage for the date range
            // Skip listings that already have locked schedules (matching SP logic: NOT EXISTS verified)
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
                        // Skip listings that already belong to a protected schedule: either
                        // locked/verified OR carrying generated kanbans. The kanban check keeps
                        // this in sync with ScheduleCleanupService::applyHasKanbanGuard so a
                        // protected-but-unlocked schedule is not re-generated into a duplicate.
                        ->where(function ($q) {
                            $q->where('assy_schedule.is_lock', '!=', 0)
                                ->orWhereExists(function ($k) {
                                    $k->select(DB::raw(1))
                                        ->from('assy_schedule_circuit')
                                        ->whereColumn('assy_schedule_circuit.assy_schedule_id', 'assy_schedule.id');
                                })
                                ->orWhereExists(function ($k) {
                                    $k->select(DB::raw(1))
                                        ->from('assy_schedule_shikake')
                                        ->whereColumn('assy_schedule_shikake.assy_schedule_id', 'assy_schedule.id');
                                });
                        });
                })
                ->orderBy('id_listing', 'asc')
                ->orderBy('listing_date_time', 'asc')
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
                    'success'     => true,
                    'step_failed' => null,
                    'message'     => 'Tidak ada data listing ditemukan untuk rentang tanggal yang dipilih.',
                    'generated'   => 0,
                    'sync_detail' => $syncDetail,
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
                    $conveyor->id
                );

                // Step 6: Delete only unlocked schedules
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

                // Step 8: Pre-map CO5 need per shift based on total qty vs shift capacities
                // 2-shift: CO5 activates AFTER all CO1-4 (both shifts) are exhausted
                // 1-shift: CO5 activates after CO1-4 of the single shift
                $totalQty = $groupListings->sum('rem_qty');
                $co5Needed = $this->capacityCalculator->preMapCutoff5(
                    $shiftCapacities, $shiftCapacity, $totalQty, $maxShifts
                );

                Log::info("CO5 pre-mapping result", [
                    'conveyor_id'     => $conveyor->id,
                    'schedule_date'   => $scheduleDate->format('Y-m-d'),
                    'total_qty'       => $totalQty,
                    'co5_needed'      => $co5Needed,
                    'shift_capacities'=> $shiftCapacities,
                ]);

                // Step 9: Allocate (budgets pre-mapped by preMapCutoff5)
                //   2-shift: S1-CO1→4 → S2-CO1→4 → S1-CO5 (capped at nominal) → S2-CO5
                //            (catch-all = all remaining)
                //   1-shift: CO1 → CO2 → CO3 → CO4 → CO5 (catch-all = all remaining)
                if ($maxShifts >= 2) {
                    // Phase 1: CO1-4 for every unlocked shift (base capacity)
                    foreach (range(1, $maxShifts) as $shift) {
                        if (($shiftLockStatus[$shift] ?? false) || !isset($shiftCapacities[$shift])) continue;

                        $result = $this->listingAllocator->allocateToShift(
                            $groupListings,
                            $shiftCapacities[$shift],
                            $shift,
                            $conveyor->id,
                            $scheduleDate->format('Y-m-d'),
                            [1, 2, 3, 4]
                        );
                        $schedulesToCreate = array_merge($schedulesToCreate, $result['schedules']);
                        if ($groupListings->sum('rem_qty') <= 0) break;
                    }

                    // Phase 2: CO5 forward — S1.CO5 (capped at nominal) first, then
                    // S2.CO5 (catch-all = all remaining). Budgets set by preMapCutoff5.
                    foreach (range(1, $maxShifts) as $shift) {
                        if ($groupListings->sum('rem_qty') <= 0) break;
                        if (($shiftLockStatus[$shift] ?? false) || !isset($shiftCapacities[$shift])) continue;

                        if (($shiftCapacities[$shift]['c5'] ?? 0) > 0) {
                            $result = $this->listingAllocator->allocateToShift(
                                $groupListings,
                                $shiftCapacities[$shift],
                                $shift,
                                $conveyor->id,
                                $scheduleDate->format('Y-m-d'),
                                [5]
                            );
                            $schedulesToCreate = array_merge($schedulesToCreate, $result['schedules']);
                        }
                    }
                } else {
                    // ── 1-shift: CO1-4 → CO5 (pre-mapped; single shift is the last shift, so CO5 = catch-all) ──
                    for ($shift = 1; $shift <= $maxShifts; $shift++) {
                        if ($shiftLockStatus[$shift] ?? false) continue;
                        if (!isset($shiftCapacities[$shift])) continue;

                        // CO1-4 dulu
                        $result = $this->listingAllocator->allocateToShift(
                            $groupListings,
                            $shiftCapacities[$shift],
                            $shift,
                            $conveyor->id,
                            $scheduleDate->format('Y-m-d'),
                            [1, 2, 3, 4]
                        );
                        $schedulesToCreate = array_merge($schedulesToCreate, $result['schedules']);

                        // CO5: gunakan budget pre-mapped (catch-all = seluruh sisa listing)
                        if (($shiftCapacities[$shift]['c5'] ?? 0) > 0) {
                            $result = $this->listingAllocator->allocateToShift(
                                $groupListings,
                                $shiftCapacities[$shift],
                                $shift,
                                $conveyor->id,
                                $scheduleDate->format('Y-m-d'),
                                [5]
                            );
                            $schedulesToCreate = array_merge($schedulesToCreate, $result['schedules']);
                        }
                        if ($groupListings->sum('rem_qty') === 0) break;
                    }
                }
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
                'success'     => true,
                'step_failed' => null,
                'message'     => "Berhasil membuat {$generatedCount} schedule.",
                'generated'   => $generatedCount,
                'sync_detail' => $syncDetail,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Schedule generation failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return [
                'success'     => false,
                'step_failed' => 'generate',
                'message'     => 'Gagal melakukan generate schedule: ' . $e->getMessage(),
                'sync_detail' => $syncDetail,
                'generated'   => 0,
            ];
        }
    }

    /**
     * Get schedules for datatable
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
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
                ->orderBy('listing_id')
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
                ->orderBy('listing_id');

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
