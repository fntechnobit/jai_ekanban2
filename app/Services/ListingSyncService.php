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
     * @param int $days Number of days to sync (default 7)
     * @return array
     */
    public function syncListingData($days = 7)
    {
        try {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

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
                        'listing_date_time' => $listing->time,
                        'conveyor' => $listing->cv ?? '',
                        'shift' => $listing->shift ?? 0,
                        'assycode' => $listing->assycode ?? '',
                        'assy' => $listing->assy ?? '',
                        'qty' => $listing->qty ?? 0,
                        'seq' => $listing->seq ?? 0,
                        'plt' => $listing->plt ?? 0,
                        'mode' => $listing->mode ?? 0,
                        'snp' => $listing->snp ?? 0,
                        'snpa' => $listing->snpa ?? 0,
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
