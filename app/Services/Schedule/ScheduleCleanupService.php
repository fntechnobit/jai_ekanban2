<?php

namespace App\Services\Schedule;

use App\Models\AssySchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleCleanupService
{
    /**
     * Safety guard: a schedule that already has generated kanban cards
     * (circuit/shikake) must NEVER be silently auto-deleted by a sync/generate
     * run. In correct operation such a schedule is is_lock=1 (already protected),
     * so this only adds protection for inconsistent "unlocked-but-has-kanban"
     * states, preventing the printed kanban list from disappearing.
     */
    private function applyHasKanbanGuard($query)
    {
        return $query
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('assy_schedule_circuit')
                    ->whereColumn('assy_schedule_circuit.assy_schedule_id', 'assy_schedule.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('assy_schedule_shikake')
                    ->whereColumn('assy_schedule_shikake.assy_schedule_id', 'assy_schedule.id');
            });
    }
    /**
     * Delete unverified (unlocked) schedules for specific shifts
     * Preserves locked schedules (is_lock = 1)
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
        
        $query = AssySchedule::whereDate('schedule', $date)
            ->where('conveyor_id', $conveyorId)
            ->whereIn('shift', $unlockedShifts)
            ->where('is_lock', 0); // Only delete unlocked schedules
        $this->applyHasKanbanGuard($query); // never delete a schedule that has generated kanbans
        $deletedCount = $query->delete();
        
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
     * Preserves locked schedules
     * 
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param int|null $conveyorId Optional conveyor filter
     * @return int Number of deleted records
     */
    public function deleteUnlockedSchedulesInRange(Carbon $startDate, Carbon $endDate, ?int $conveyorId = null): int
    {
        $query = AssySchedule::whereBetween('schedule', [$startDate, $endDate])
            ->where('is_lock', 0);

        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }

        $this->applyHasKanbanGuard($query); // never delete a schedule that has generated kanbans

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
