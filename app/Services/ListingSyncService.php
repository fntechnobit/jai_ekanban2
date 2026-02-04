<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListingSyncService
{
    /**
     * Synchronize listing data from mysql_listing to listing_stage
     *
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array
     */
    public function syncListingData($startDate, $endDate)
    {
        try {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            // Fetch data from mysql_listing
            $listings = Listing::whereBetween('time', [$startDate, $endDate])
                ->orderBy('time', 'desc')
                ->get();

            $syncedCount = 0;
            $skippedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($listings as $listing) {
                try {
                    // Check if record already exists
                    $exists = ListingStage::where('listing_date_time', $listing->time)
                        ->where('conveyor', $listing->cv)
                        ->where('assycode', $listing->assycode)
                        ->where('seq', $listing->seq)
                        ->exists();

                    if ($exists) {
                        $skippedCount++;
                        continue;
                    }

                    // Create new listing_stage record
                    ListingStage::create([
                        'id_listing' => $listing->id_listing,
                        'listing_date_time' => $listing->time,
                        'conveyor' => $listing->cv ?? '',
                        'shift' => $listing->shift ?? 0,
                        'assycode' => $listing->assycode ?? '',
                        'assy' => $listing->assy ?? '',
                        'carline' => $listing->carline ?? '',
                        'qty' => $listing->qty ?? 0,
                        'seq' => $listing->seq ?? 0,
                        'plt' => $listing->plt ?? 0,
                        'mode' => $listing->mode ?? 0,
                        'snp' => $listing->snp ?? 0,
                        'snpa' => $listing->snpa ?? 0,
                        'synced_at' => now(),
                    ]);

                    $syncedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error syncing record ID {$listing->id_listing}: " . $e->getMessage();
                    Log::error("Listing sync error", [
                        'listing_id' => $listing->id_listing,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'total_records' => $listings->count(),
                'synced' => $syncedCount,
                'skipped' => $skippedCount,
                'errors' => $errors,
                'date_range' => [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d')
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Listing sync failed", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Synchronization failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Delete listing_stage data for the specified date range
     *
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array
     */
    public function deleteListingStageData($startDate, $endDate)
    {
        try {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            // Delete only listing_stage records that do NOT have protected assy_schedules
            // Protected schedules are: locked (is_lock != 0), user-edited (is_user_edited = 1), or soft-deleted (deleted_at IS NOT NULL)
            // IMPORTANT: Because assy_schedule has ON DELETE CASCADE on listing_id,
            // deleting listing_stage will hard-delete associated assy_schedule records,
            // bypassing Laravel's SoftDeletes. So we must protect all three cases here.
            $deletedCount = ListingStage::whereBetween('listing_date_time', [$startDate, $endDate])
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('assy_schedule')
                        ->whereColumn('assy_schedule.listing_id', 'listing_stage.id')
                        ->where(function ($q) {
                            $q->where('assy_schedule.is_lock', '!=', 0)
                              ->orWhere('assy_schedule.is_user_edited', '=', 1)
                              ->orWhereNotNull('assy_schedule.deleted_at');
                        });
                })
                ->delete();

            // Count protected records for logging
            $protectedCount = ListingStage::whereBetween('listing_date_time', [$startDate, $endDate])
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('assy_schedule')
                        ->whereColumn('assy_schedule.listing_id', 'listing_stage.id')
                        ->where(function ($q) {
                            $q->where('assy_schedule.is_lock', '!=', 0)
                              ->orWhere('assy_schedule.is_user_edited', '=', 1)
                              ->orWhereNotNull('assy_schedule.deleted_at');
                        });
                })
                ->count();

            Log::info("Deleted listing_stage records (protected: locked, user-edited, soft-deleted schedules)", [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'deleted_count' => $deletedCount,
                'protected_count' => $protectedCount
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'protected_count' => $protectedCount,
                'date_range' => [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d')
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Failed to delete listing_stage data", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to delete listing_stage data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get sync statistics
     *
     * @return array
     */
    public function getSyncStatistics()
    {
        $latestInListing = Listing::orderBy('time', 'desc')->first();
        $latestInStage = ListingStage::orderBy('listing_date_time', 'desc')->first();
        $totalInStage = ListingStage::count();

        return [
            'latest_in_listing' => $latestInListing ? $latestInListing->time : null,
            'latest_in_stage' => $latestInStage ? $latestInStage->listing_date_time : null,
            'total_in_stage' => $totalInStage,
        ];
    }
}
