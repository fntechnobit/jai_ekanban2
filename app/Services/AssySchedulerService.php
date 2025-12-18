<?php

namespace App\Services;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssySchedulerService
{
    protected $listingSyncService;

    public function __construct(ListingSyncService $listingSyncService)
    {
        $this->listingSyncService = $listingSyncService;
    }
    /**
     * Generate assy schedules for the specified date range
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

            // Step 3: Delete existing assy_schedule records for the date range
            $deletedCount = $this->deleteExistingSchedules($startDate, $endDate, $conveyorId);

            // Step 4: Get fresh listing data from listing_stage for the date range
            $listingsQuery = ListingStage::whereBetween('listing_date_time', [$startDate, $endDate])
                ->whereNotNull('assycode')
                ->where('assycode', '!=', '')
                ->whereNotNull('assy')
                ->where('assy', '!=', '')
                ->where('qty', '>', 0)
                ->orderBy('listing_date_time', 'asc')
                ->orderBy('seq', 'asc')
                ->orderBy('assycode', 'asc')
                ->orderBy('id', 'asc');

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

            foreach ($groupedListings as $groupKey => $groupListings) {
                list($date, $conveyorName) = explode('_', $groupKey, 2);
                
                // Get conveyor from master_conveyor
                $conveyor = MasterConveyor::where('conveyor', $conveyorName)->first();
                
                // If conveyor not found, skip this group
                if (!$conveyor) {
                    Log::warning("Conveyor not found: {$conveyorName}");
                    continue;
                }

                // Get conveyor capacity per shift (default to 100 if not set)
                $shiftCapacity = $conveyor->capacity ?? 100;
                
                // Get max shifts available for this conveyor (default to 3 if not set)
                $maxShifts = $conveyor->shift_qty ?? 2;

                // Reset shift and capacity for each new group (conveyor + date combination)
                $currentShift = 1;
                $currentCapacity = 0;
                $scheduleDate = Carbon::parse($date);

                foreach ($groupListings as $listing) {
                    $remainingQty = $listing->qty ?? 1;

                    // Process the listing until all quantity is allocated or max shifts reached
                    while ($remainingQty > 0 && $currentShift <= $maxShifts) {
                        // Calculate how much capacity is available in current shift
                        $availableCapacity = $shiftCapacity - $currentCapacity;
                        
                        // If current shift is full, move to next shift
                        if ($availableCapacity <= 0) {
                            $currentShift++;
                            $currentCapacity = 0;
                            
                            // Check if we exceeded max shifts
                            if ($currentShift > $maxShifts) {
                                Log::info("Max shifts ({$maxShifts}) reached for {$conveyorName} on {$date}, remaining qty: {$remainingQty}");
                                break;
                            }
                            
                            $availableCapacity = $shiftCapacity;
                        }

                        // Determine how much qty to allocate to this shift
                        $qtyForThisShift = min($remainingQty, $availableCapacity);

                        // Create assy_schedule record for this shift portion
                        AssySchedule::create([
                            'schedule' => $scheduleDate,
                            'conveyor_id' => $conveyor->id,
                            'listing_id' => $listing->id,
                            'shift' => $currentShift,
                            'assycode' => $listing->assycode,
                            'assy' => $listing->assy,
                            'qty' => $qtyForThisShift,
                            'seq' => $listing->seq,
                            'mode' => $listing->mode,
                            'snp' => $listing->snp,
                            'snpa' => $listing->snpa,
                            'is_lock' => 0,
                            'created_by' => Auth::id(),
                        ]);

                        // Update counters
                        $currentCapacity += $qtyForThisShift;
                        $remainingQty -= $qtyForThisShift;
                        $generatedCount++;
                    }
                }
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
}
