<?php

namespace App\Services;

use App\Models\AssySchedule;
use App\Models\AssyScheduleCircuit;
use App\Models\AssyScheduleShikake;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get printing statistics for the last 7 days
     *
     * @return array
     */
    public function getPrintingStats()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();

        // Circuits printed in last 7 days
        $circuitsPrinted = AssyScheduleCircuit::where('is_printed', true)
            ->where('last_printed_at', '>=', $sevenDaysAgo)
            ->count();

        // Shikakes printed in last 7 days
        $shikakesPrinted = AssyScheduleShikake::where('is_printed', true)
            ->where('last_printed_at', '>=', $sevenDaysAgo)
            ->count();

        // Total print count (sum of all print_count values)
        $totalCircuitPrintCount = AssyScheduleCircuit::where('is_printed', true)
            ->where('last_printed_at', '>=', $sevenDaysAgo)
            ->sum('print_count');

        $totalShikakePrintCount = AssyScheduleShikake::where('is_printed', true)
            ->where('last_printed_at', '>=', $sevenDaysAgo)
            ->sum('print_count');

        $totalPrintCount = $totalCircuitPrintCount + $totalShikakePrintCount;

        return [
            'circuits_printed' => $circuitsPrinted,
            'shikakes_printed' => $shikakesPrinted,
            'total_print_count' => $totalPrintCount,
        ];
    }

    /**
     * Get schedule overview for the last 7 days
     *
     * @return array
     */
    public function getScheduleOverview()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();

        // Get unique schedule groups (conveyor_id + date + shift combination)
        $allSchedules = AssySchedule::where('schedule', '>=', $sevenDaysAgo)
            ->select(
                'conveyor_id',
                DB::raw('DATE(schedule) as schedule_date'),
                'shift',
                DB::raw('MAX(is_lock) as is_verified')
            )
            ->groupBy('conveyor_id', 'schedule_date', 'shift')
            ->get();

        $totalSchedules = $allSchedules->count();
        $verifiedSchedules = $allSchedules->where('is_verified', 1)->count();
        $pendingSchedules = $allSchedules->where('is_verified', 0)->count();

        // Total assy items (sum of qty)
        $totalAssyItems = AssySchedule::where('schedule', '>=', $sevenDaysAgo)
            ->sum('qty');

        return [
            'total_schedules' => $totalSchedules,
            'verified_schedules' => $verifiedSchedules,
            'pending_schedules' => $pendingSchedules,
            'total_assy_items' => $totalAssyItems,
        ];
    }

    /**
     * Get recent activity feed grouped by type
     *
     * @param int $limit
     * @return array
     */
    public function getRecentActivity($limit = 10)
    {
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();

        // Get recent creations
        $creations = AssySchedule::with(['creator', 'conveyor'])
            ->where('created_at', '>=', $sevenDaysAgo)
            ->whereNotNull('created_by')
            ->select('id', 'conveyor_id', 'schedule', 'shift', 'created_by', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($schedule) {
                return [
                    'type' => 'creation',
                    'user_name' => $schedule->creator->name ?? 'Unknown',
                    'timestamp' => $schedule->created_at,
                    'conveyor' => $schedule->conveyor->conveyor ?? 'N/A',
                    'schedule_date' => $schedule->schedule->format('Y-m-d'),
                    'shift' => $schedule->shift,
                ];
            });

        // Get recent verifications
        $verifications = AssySchedule::with(['verifier', 'conveyor'])
            ->where('verified_at', '>=', $sevenDaysAgo)
            ->whereNotNull('verified_by')
            ->select('id', 'conveyor_id', 'schedule', 'shift', 'verified_by', 'verified_at')
            ->orderBy('verified_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($schedule) {
                return [
                    'type' => 'verification',
                    'user_name' => $schedule->verifier->name ?? 'Unknown',
                    'timestamp' => $schedule->verified_at,
                    'conveyor' => $schedule->conveyor->conveyor ?? 'N/A',
                    'schedule_date' => $schedule->schedule->format('Y-m-d'),
                    'shift' => $schedule->shift,
                ];
            });

        // Get recent circuit prints
        $circuitPrints = AssyScheduleCircuit::with(['printedBy', 'assySchedule.conveyor'])
            ->where('last_printed_at', '>=', $sevenDaysAgo)
            ->whereNotNull('last_printed_by')
            ->select('id', 'assy_schedule_id', 'last_printed_by', 'last_printed_at')
            ->orderBy('last_printed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($circuit) {
                return [
                    'type' => 'print_circuit',
                    'user_name' => $circuit->printedBy->name ?? 'Unknown',
                    'timestamp' => $circuit->last_printed_at,
                    'conveyor' => $circuit->assySchedule->conveyor->conveyor ?? 'N/A',
                    'schedule_date' => $circuit->assySchedule->schedule->format('Y-m-d'),
                    'shift' => $circuit->assySchedule->shift,
                ];
            });

        // Get recent shikake prints
        $shikakePrints = AssyScheduleShikake::with(['printedBy', 'assySchedule.conveyor'])
            ->where('last_printed_at', '>=', $sevenDaysAgo)
            ->whereNotNull('last_printed_by')
            ->select('id', 'assy_schedule_id', 'last_printed_by', 'last_printed_at')
            ->orderBy('last_printed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($shikake) {
                return [
                    'type' => 'print_shikake',
                    'user_name' => $shikake->printedBy->name ?? 'Unknown',
                    'timestamp' => $shikake->last_printed_at,
                    'conveyor' => $shikake->assySchedule->conveyor->conveyor ?? 'N/A',
                    'schedule_date' => $shikake->assySchedule->schedule->format('Y-m-d'),
                    'shift' => $shikake->assySchedule->shift,
                ];
            });

        return [
            'creations' => $creations,
            'verifications' => $verifications,
            'prints' => $circuitPrints->concat($shikakePrints)->sortByDesc('timestamp')->take($limit)->values(),
        ];
    }

    /**
     * Get printing trend data for the last 7 days
     *
     * @return array
     */
    public function getPrintingTrend()
    {
        $days = [];
        $circuitData = [];
        $shikakeData = [];

        // Generate last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->startOfDay();
            $endOfDay = $date->copy()->endOfDay();
            
            $days[] = $date->format('M d');

            // Count circuits printed on this day
            $circuitCount = AssyScheduleCircuit::where('is_printed', true)
                ->whereBetween('last_printed_at', [$date, $endOfDay])
                ->count();
            $circuitData[] = $circuitCount;

            // Count shikakes printed on this day
            $shikakeCount = AssyScheduleShikake::where('is_printed', true)
                ->whereBetween('last_printed_at', [$date, $endOfDay])
                ->count();
            $shikakeData[] = $shikakeCount;
        }

        return [
            'labels' => $days,
            'circuits' => $circuitData,
            'shikakes' => $shikakeData,
        ];
    }
}
