<?php

namespace App\Services\Schedule;

use App\Models\AssySchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduleCleanupService
{
    /**
     * Delete unverified (unlocked) schedules for specific shifts
     * Preserves locked schedules (is_lock = 1) and user-edited schedules (is_user_edited = 1)
     * 
     * @param string $date Date in Y-m-d format
     * @param int $conveyorId Conveyor ID
     * @param array $unlockedShifts Array of shift numbers to clean
     * @return int Number of deleted records
     */
    public function deleteUnverifiedSchedules(string $date, int $conveyorId, array $unlockedShifts): int
    {
        if (empty($unlockedShifts)) {
            Log::info("No unlocked shifts to clean", [
                'date' => $date,
                'conveyor_id' => $conveyorId
            ]);
            return 0;
        }
        
        $date = Carbon::parse($date);
        
        $deletedCount = AssySchedule::whereDate('schedule', $date)
            ->where('conveyor_id', $conveyorId)
            ->whereIn('shift', $unlockedShifts)
            ->where('is_lock', 0) // Only delete unlocked schedules
            ->where('is_user_edited', 0) // Preserve user-edited schedules
            ->delete();
        
        Log::info("Deleted unlocked schedules", [
            'date' => $date->format('Y-m-d'),
            'conveyor_id' => $conveyorId,
            'shifts' => $unlockedShifts,
            'deleted_count' => $deletedCount
        ]);
        
        return $deletedCount;
    }
    
    /**
     * Delete all unlocked schedules for a date range
     * Preserves locked schedules, user-edited schedules, and soft-deleted schedules
     * 
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param int|null $conveyorId Optional conveyor filter
     * @return int Number of deleted records
     */
    public function deleteUnlockedSchedulesInRange(Carbon $startDate, Carbon $endDate, ?int $conveyorId = null): int
    {
        $query = AssySchedule::whereBetween('schedule', [$startDate, $endDate])
            ->where('is_lock', 0)
            ->where('is_user_edited', 0);
            
        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }
        
        // Log what will be preserved before deletion
        $preservedCount = AssySchedule::withTrashed()
            ->whereBetween('schedule', [$startDate, $endDate])
            ->where(function($q) {
                $q->where('is_lock', 1)
                  ->orWhere('is_user_edited', 1)
                  ->orWhereNotNull('deleted_at');
            })
            ->when($conveyorId, fn($q) => $q->where('conveyor_id', $conveyorId))
            ->count();
        
        Log::info("Schedules to be preserved (locked/user-edited/soft-deleted)", [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'conveyor_id' => $conveyorId,
            'preserved_count' => $preservedCount
        ]);
        
        $deletedCount = $query->delete();
        
        Log::info("Deleted unlocked schedules in range", [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'conveyor_id' => $conveyorId,
            'deleted_count' => $deletedCount
        ]);
        
        return $deletedCount;
    }
}
