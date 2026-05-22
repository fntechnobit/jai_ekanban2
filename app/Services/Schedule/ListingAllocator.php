<?php

namespace App\Services\Schedule;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ListingAllocator
{
    /**
     * Allocate listings to a specific cutoff
     * Processes listings sequentially until cutoff capacity is exhausted
     * 
     * @param Collection $listings Collection with 'rem_qty' tracking field
     * @param int $cutoffCapacity Available capacity for this cutoff
     * @param int $shift Shift number
     * @param int $cutoff Cutoff number (1-5)
     * @param int $conveyorId Conveyor ID
     * @param string $scheduleDate Schedule date
     * @return array ['schedules' => array, 'capacity_used' => int]
     */
    private function allocateToCutoff(
        Collection $listings, 
        int $cutoffCapacity, 
        int $shift, 
        int $cutoff,
        int $conveyorId,
        string $scheduleDate
    ): array
    {
        $schedules = [];
        $capacityUsed = 0;
        
        while ($cutoffCapacity > 0) {
            // Get first listing with remaining quantity
            $listing = $listings->first(function($item) {
                return ($item->rem_qty ?? 0) > 0;
            });
            
            if (!$listing) {
                break; // No more listings to allocate
            }
            
            // Calculate how much to take from this listing
            $take = min($listing->rem_qty, $cutoffCapacity);
            
            // Create schedule record data
            $schedules[] = [
                'schedule' => $scheduleDate,
                'conveyor_id' => $conveyorId,
                'shift' => $shift,
                'cutoff' => $cutoff,
                'listing_id' => $listing->id,
                'assycode' => $listing->assycode,
                'assy' => $listing->assy,
                'qty' => $take,
                'seq' => $listing->seq,
                'plt' => $listing->plt ?? null,
                'mode' => $listing->mode,
                'snp' => $listing->snp,
                'snpa' => $listing->snpa,
                'is_lock' => 0, // New schedules are unlocked by default
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Update tracking
            $listing->rem_qty -= $take;
            $cutoffCapacity -= $take;
            $capacityUsed += $take;
        }
        
        return [
            'schedules' => $schedules,
            'capacity_used' => $capacityUsed
        ];
    }
    
    /**
     * Allocate listings to specified cutoffs for a shift.
     * CO5 capacity is pre-mapped by ShiftCapacityCalculator (0 if not needed)
     *
     * @param Collection $listings Listings to allocate (modified in place)
     * @param array $cutoffCapacities ['c1' => 25, 'c2' => 25, 'c3' => 25, 'c4' => 25, 'c5' => 0|N]
     * @param int $shift Shift number
     * @param int $conveyorId Conveyor ID
     * @param string $scheduleDate Schedule date
     * @param array $cutoffsToProcess Which cutoffs to process (default: all 1-5)
     * @return array ['schedules' => array, 'capacity_used' => int]
     */
    public function allocateToShift(
        Collection $listings,
        array $cutoffCapacities,
        int $shift,
        int $conveyorId,
        string $scheduleDate,
        array $cutoffsToProcess = [1, 2, 3, 4, 5]
    ): array
    {
        $allSchedules = [];
        $totalCapacityUsed = 0;

        foreach ($cutoffsToProcess as $cutoff) {
            $cutoffKey = "c{$cutoff}";
            $capacity = $cutoffCapacities[$cutoffKey] ?? 0;

            if ($capacity > 0) {
                $result = $this->allocateToCutoff(
                    $listings,
                    $capacity,
                    $shift,
                    $cutoff,
                    $conveyorId,
                    $scheduleDate
                );
                $allSchedules = array_merge($allSchedules, $result['schedules']);
                $totalCapacityUsed += $result['capacity_used'];
            }
        }

        return [
            'schedules' => $allSchedules,
            'capacity_used' => $totalCapacityUsed
        ];
    }
    
    /**
     * Initialize tracking field for listings
     * Adds 'rem_qty' field to track remaining quantity during allocation
     * 
     * @param Collection $listings Listings to initialize
     * @return void
     */
    public function initializeListings(Collection $listings): void
    {
        foreach ($listings as $listing) {
            $listing->rem_qty = $listing->qty ?? 0;
        }
    }
}
