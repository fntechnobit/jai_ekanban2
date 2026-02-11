<?php

namespace App\Services\Schedule;

use App\Models\AssySchedule;
use Carbon\Carbon;

class ShiftLockChecker
{
    /**
     * Get lock status for all shifts on a specific date and conveyor
     * 
     * @param string $date Date in Y-m-d format
     * @param int $conveyorId Conveyor ID
     * @return array Lock status indexed by shift number [1 => bool, 2 => bool]
     */
    public function getShiftLockStatus(string $date, int $conveyorId): array
    {
        $date = Carbon::parse($date);
        
        // Get all shifts that have at least one locked schedule
        $lockedShifts = AssySchedule::whereDate('schedule', $date)
            ->where('conveyor_id', $conveyorId)
            ->where('is_lock', 1)
            ->distinct()
            ->pluck('shift')
            ->toArray();
        
        // Build status array - assuming max 2 shifts
        $status = [];
        for ($shift = 1; $shift <= 2; $shift++) {
            $status[$shift] = in_array($shift, $lockedShifts);
        }
        
        return $status;
    }
    
    /**
     * Check if a specific shift is locked
     * 
     * @param string $date Date in Y-m-d format
     * @param int $conveyorId Conveyor ID
     * @param int $shift Shift number
     * @return bool True if shift is locked
     */
    public function isShiftLocked(string $date, int $conveyorId, int $shift): bool
    {
        $date = Carbon::parse($date);
        
        return AssySchedule::whereDate('schedule', $date)
            ->where('conveyor_id', $conveyorId)
            ->where('shift', $shift)
            ->where('is_lock', 1)
            ->exists();
    }
    
    /**
     * Get unlocked shifts for a date and conveyor
     * 
     * @param string $date Date in Y-m-d format
     * @param int $conveyorId Conveyor ID
     * @param int $maxShifts Maximum number of shifts
     * @return array Array of unlocked shift numbers
     */
    public function getUnlockedShifts(string $date, int $conveyorId, int $maxShifts = 2): array
    {
        $lockStatus = $this->getShiftLockStatus($date, $conveyorId);
        $unlockedShifts = [];
        
        for ($shift = 1; $shift <= $maxShifts; $shift++) {
            if (!($lockStatus[$shift] ?? false)) {
                $unlockedShifts[] = $shift;
            }
        }
        
        return $unlockedShifts;
    }
}
