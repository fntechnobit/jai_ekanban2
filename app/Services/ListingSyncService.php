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

            // Explicit connection test — throw immediately if mysql_listing is unreachable
            try {
                DB::connection('mysql_listing')->getPdo();
            } catch (\Exception $e) {
                Log::error("mysql_listing connection failed", ['error' => $e->getMessage()]);
                return [
                    'success' => false,
                    'message' => 'Tidak dapat terhubung ke database listing (PPC): ' . $e->getMessage(),
                    'errors'  => [$e->getMessage()],
                ];
            }

            // Fetch data from mysql_listing ordered by id_listing ASC
            // agar listing_stage.id auto-increment mencerminkan urutan listing asli dari source
            $listings = Listing::whereBetween('time', [$startDate, $endDate])
                ->orderBy('id_listing', 'asc')
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

            // A listing_stage row is PROTECTED from deletion when it is referenced by an
            // assy_schedule that is either locked/verified (is_lock != 0) OR already has
            // generated kanban cards. The kanban guard prevents the FK ON DELETE CASCADE
            // chain (listing_stage -> assy_schedule -> assy_schedule_circuit/shikake) from
            // silently wiping a printed kanban list even if the schedule's lock flag is
            // inconsistent. In normal operation kanban rows only exist on locked schedules,
            // so this does not change behaviour for clean data.
            $protectionFilter = function ($query) {
                $query->select(DB::raw(1))
                    ->from('assy_schedule')
                    ->whereColumn('assy_schedule.listing_id', 'listing_stage.id')
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
            };

            // Delete only listing_stage records that are NOT protected
            $deletedCount = ListingStage::whereBetween('listing_date_time', [$startDate, $endDate])
                ->whereNotExists($protectionFilter)
                ->delete();

            // Count protected records for logging
            $protectedCount = ListingStage::whereBetween('listing_date_time', [$startDate, $endDate])
                ->whereExists($protectionFilter)
                ->count();

            Log::info("Deleted listing_stage records (protected locked schedules)", [
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
