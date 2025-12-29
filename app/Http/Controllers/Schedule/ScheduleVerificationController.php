<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\MasterConveyor;
use App\Models\AssySchedule;
use App\Models\Listing;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ScheduleVerificationController extends Controller
{
    /**
     * Display the schedule verification page
     */
    public function index()
    {
        $conveyors = MasterConveyor::orderBy('conveyor', 'asc')->get();

        return view('schedule.schedule_verification.index', compact('conveyors'));
    }

    /**
     * Get datatable data for schedule verification
     */
    public function datatable(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $conveyorId = $request->input('conveyor_id');

        // Build query to get grouped data
        $query = AssySchedule::with('conveyor')
            ->select(
                'conveyor_id',
                DB::raw('DATE(schedule) as schedule_date'),
                'shift',
                DB::raw('GROUP_CONCAT(DISTINCT assy ORDER BY assy SEPARATOR ", ") as assy_list'),
                DB::raw('SUM(qty) as total_listing'),
                DB::raw('MAX(is_lock) as is_lock'),
                DB::raw('MIN(id) as first_id')
            )
            ->groupBy('conveyor_id', 'schedule_date', 'shift');

        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween('schedule', [$startDate, $endDate]);
        }

        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }

        $query->orderBy('schedule_date', 'asc')
              ->orderBy('conveyor_id', 'asc')
              ->orderBy('shift', 'asc');

        $schedules = $query->get();

        return DataTables::of($schedules)
            ->addIndexColumn()
            ->addColumn('conveyor_name', function ($schedule) {
                return $schedule->conveyor ? $schedule->conveyor->conveyor : '-';
            })
            ->addColumn('dates', function ($schedule) {
                return Carbon::parse($schedule->schedule_date)->format('Y-m-d');
            })
            ->addColumn('shift_name', function ($schedule) {
                return 'Shift ' . $schedule->shift;
            })
            ->addColumn('capacity', function ($schedule) {
                if ($schedule->conveyor) {
                    // Get the capacity per shift (assuming capacity is daily, divide by 2 for 2 shifts)
                    return $schedule->conveyor->capacity;
                }
                return 0;
            })
            ->addColumn('listing', function ($schedule) {
                return $schedule->total_listing;
            })
            ->addColumn('assy', function ($schedule) {
                return $schedule->assy_list ?: '-';
            })
            ->addColumn('status', function ($schedule) {
                if ($schedule->is_lock == 1) {
                    return '<span class="badge badge-success">Verified</span>';
                }
                return '<span class="badge badge-danger">Pending</span>';
            })
            ->addColumn('action', function ($schedule) {
                $verifyBtn = '<button type="button" class="btn btn-warning btn-sm btn-verify" 
                    data-conveyor-id="' . $schedule->conveyor_id . '" 
                    data-date="' . $schedule->schedule_date . '" 
                    data-shift="' . $schedule->shift . '">
                    <i class="fas fa-check"></i> Verify
                </button>';
                
                return $verifyBtn;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Get verification details for a specific schedule
     */
    public function details(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $date = $request->input('date');
        $shift = $request->input('shift');

        // Get the conveyor
        $conveyor = MasterConveyor::find($conveyorId);
        
        if (!$conveyor) {
            return response()->json([
                'success' => false,
                'message' => 'Conveyor not found'
            ], 404);
        }

        // Get all schedules for this conveyor, date, and shift
        $schedules = AssySchedule::where('conveyor_id', $conveyorId)
            ->whereDate('schedule', $date)
            ->where('shift', $shift)
            ->orderBy('cutoff', 'asc')
            ->orderBy('seq', 'asc')
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No schedules found'
            ], 404);
        }

        // Group by cut off
        $cutOffs = $schedules->groupBy('cutoff')->map(function ($items, $cutoff) {
            return [
                'cutoff' => $cutoff,
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'assy' => $item->assy,
                        'qty' => $item->qty,
                        'cutoff' => $item->cutoff
                    ];
                })->values()->toArray()
            ];
        })->values()->toArray();

        // Get unique assy count
        $assyCount = $schedules->pluck('assy')->unique()->count();
        $totalListing = $schedules->sum('qty');

        return response()->json([
            'success' => true,
            'conveyor_id' => $conveyorId,
            'conveyor' => $conveyor->conveyor,
            'date' => $date,
            'shift' => $shift,
            'capacity' => $conveyor->capacity,
            'assy_count' => $assyCount,
            'total_listing' => $totalListing,
            'cut_offs' => $cutOffs
        ]);
    }

    /**
     * Save verification changes
     */
    public function save(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|integer',
            'date' => 'required|date',
            'shift' => 'required|integer',
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|integer',
            'schedules.*.cutoff' => 'required|integer',
            'schedules.*.qty' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $schedules = $request->input('schedules');

            foreach ($schedules as $scheduleData) {
                AssySchedule::where('id', $scheduleData['id'])
                    ->update([
                        'cutoff' => $scheduleData['cutoff'],
                        'qty' => $scheduleData['qty'],
                        'updated_by' => Auth::id(),
                        'updated_at' => now()
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save changes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify a schedule (placeholder - functionality to be implemented later)
     */
    public function verify(Request $request)
    {
        // Placeholder for verify functionality
        // Will be implemented later as per user requirement
        
        return response()->json([
            'success' => true,
            'message' => 'Verification functionality will be implemented later'
        ]);
    }
}
