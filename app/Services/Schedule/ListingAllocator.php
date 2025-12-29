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
     * Allocate listings to all cutoffs (1-4) for a shift
     * Processes cutoffs sequentially: c1, c2, c3, c4
     * 
     * @param Collection $listings Listings to allocate (modified in place)
     * @param array $cutoffCapacities ['c1' => 25, 'c2' => 25, 'c3' => 25, 'c4' => 25]
     * @param int $shift Shift number
     * @param int $conveyorId Conveyor ID
     * @param string $scheduleDate Schedule date
     * @return array ['schedules' => array, 'capacity_used' => int]
     */
    public function allocateToShift(
        Collection $listings, 
        array $cutoffCapacities, 
        int $shift,
        int $conveyorId,
        string $scheduleDate
    ): array
    {
        $allSchedules = [];
        $totalCapacityUsed = 0;
        
        // Process cutoffs 1-4
        for ($cutoff = 1; $cutoff <= 4; $cutoff++) {
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
     * Allocate overflow (cutoff 5) based on lock status
     * Distributes remaining quantities to unlocked shifts
     * 
     * @param Collection $remainingListings Listings with remaining quantity
     * @param array $lockStatus [1 => bool, 2 => bool]
     * @param int $maxShifts Maximum number of shifts
     * @param int $conveyorId Conveyor ID
     * @param string $scheduleDate Schedule date
     * @return array ['schedules' => array]
     */
    public function allocateOverflow(
        Collection $remainingListings, 
        array $lockStatus, 
        int $maxShifts,
        int $conveyorId,
        string $scheduleDate
    ): array
    {
        $totalRemaining = $remainingListings->sum('rem_qty');
        
        if ($totalRemaining === 0) {
            return ['schedules' => []];
        }
        
        // Determine overflow distribution
        $shift1Locked = $lockStatus[1] ?? false;
        $shift2Locked = $lockStatus[2] ?? false;
        
        if ($shift1Locked && $shift2Locked) {
            // Both locked - cannot allocate
            Log::warning("Cannot allocate overflow - all shifts locked", [
                'total_remaining' => $totalRemaining
            ]);
            return ['schedules' => []];
        }
        
        if ($shift1Locked) {
            // All overflow to shift 2
            $overflowDistribution = [2 => $totalRemaining];
        } elseif ($shift2Locked || $maxShifts === 1) {
            // All overflow to shift 1
            $overflowDistribution = [1 => $totalRemaining];
        } else {
            // Split evenly between both shifts
            $shift1Overflow = (int) floor($totalRemaining / 2);
            $shift2Overflow = $totalRemaining - $shift1Overflow;
            $overflowDistribution = [
                1 => $shift1Overflow,
                2 => $shift2Overflow
            ];
        }
        
        // Allocate overflow to cutoff 5
        $schedules = [];
        foreach ($overflowDistribution as $shift => $overflowQty) {
            if ($overflowQty > 0) {
                $result = $this->allocateToCutoff(
                    $remainingListings, 
                    $overflowQty, 
                    $shift, 
                    5, 
                    $conveyorId, 
                    $scheduleDate
                );
                $schedules = array_merge($schedules, $result['schedules']);
            }
        }
        
        Log::info("Overflow allocated to cutoff 5", [
            'total_remaining' => $totalRemaining,
            'distribution' => $overflowDistribution,
            'schedules_created' => count($schedules)
        ]);
        
        return ['schedules' => $schedules];
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
