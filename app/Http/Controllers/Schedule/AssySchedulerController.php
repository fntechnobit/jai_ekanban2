<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Services\AssySchedulerService;
use App\Models\MasterConveyor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AssySchedulerController extends Controller
{
    protected $assySchedulerService;

    public function __construct(AssySchedulerService $assySchedulerService)
    {
        $this->assySchedulerService = $assySchedulerService;
    }

    /**
     * Display the scheduler page
     */
    public function index()
    {
        $conveyors = MasterConveyor::orderBy('conveyor', 'asc')->get();

        return view('schedule.assy_scheduler.index', compact('conveyors'));
    }

    /**
     * Get datatable data
     */
    public function datatable(Request $request)
    {
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'conveyor_id' => $request->input('conveyor_id'),
        ];

        $query = $this->assySchedulerService->getSchedulesQuery($filters);
        $schedules = $query->get();

        // Group schedules by conveyor, date, and shift
        $grouped = $schedules->groupBy(function ($schedule) {
            return $schedule->conveyor_id . '_' . $schedule->schedule->format('Y-m-d') . '_' . $schedule->shift;
        })->map(function ($group) {
            $first = $group->first();
            
            return (object) [
                'id' => $first->id, // Use first record's ID for actions
                'conveyor' => $first->conveyor,
                'conveyor_id' => $first->conveyor_id,
                'schedule' => $first->schedule,
                'shift' => $first->shift,
                'listing_count' => $group->sum('qty'), // Total qty for all listings in this shift
                'assy_list' => $group->pluck('assy')->filter()->unique()->implode(', '), // All unique assy codes
                'is_lock' => $group->every('is_lock'), // All verified if every item is locked
                'group_ids' => $group->pluck('id')->toArray(), // All IDs in this group for bulk operations
            ];
        })->values();

        return DataTables::of($grouped)
            ->addIndexColumn()
            ->addColumn('conveyor_name', function ($schedule) {
                return $schedule->conveyor ? $schedule->conveyor->conveyor : '-';
            })
            ->addColumn('date', function ($schedule) {
                return $schedule->schedule->format('Y-m-d');
            })
            ->addColumn('shift_name', function ($schedule) {
                return 'Shift ' . $schedule->shift;
            })
            ->addColumn('capacity', function ($schedule) {
                return $schedule->conveyor ? $schedule->conveyor->capacity : 0;
            })
            ->addColumn('listing_count', function ($schedule) {
                return $schedule->listing_count;
            })
            ->addColumn('assy_list', function ($schedule) {
                return $schedule->assy_list ?: '-';
            })
            // ->addColumn('status', function ($schedule) {
            //     if ($schedule->is_lock) {
            //         return '<span class="badge badge-success">Verified</span>';
            //     }
            //     return '<span class="badge badge-warning">Pending</span>';
            // })
            ->addColumn('action', function ($schedule) {
                // Join all IDs with commas for bulk verification
                //$ids = implode(',', $schedule->group_ids);
                // $verifyBtn = '<button type="button" class="btn btn-warning btn-sm btn-verify" data-ids="' . $ids . '">
                //     <i class="fas fa-check"></i> Verify
                // </button>';
                
                $manageBtn = '<button type="button" class="btn btn-info btn-sm btn-manage ml-1" 
                    data-conveyor-id="' . $schedule->conveyor_id . '" 
                    data-conveyor-name="' . ($schedule->conveyor ? $schedule->conveyor->conveyor : '') . '" 
                    data-date="' . $schedule->schedule->format('Y-m-d') . '" 
                    data-capacity="' . ($schedule->conveyor ? $schedule->conveyor->capacity : 0) . '" 
                    data-max-shifts="' . ($schedule->conveyor ? $schedule->conveyor->shift_qty : 0) . '">
                    <i class="fas fa-cogs"></i> Manage
                </button>';
                
                return $manageBtn;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Generate schedules
     */
    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'conveyor_id' => 'nullable|exists:master_conveyor,id',
        ]);

        try {
            $result = $this->assySchedulerService->generateSchedules(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('conveyor_id')
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error("Schedule generation error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate schedules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify a schedule or multiple schedules
     */
    public function verify(Request $request, $id = null)
    {
        try {
            // Check if we're verifying multiple IDs (comma-separated)
            $ids = $request->input('ids');
            
            if ($ids) {
                // Bulk verification
                $idArray = explode(',', $ids);
                $results = [];
                
                foreach ($idArray as $scheduleId) {
                    $result = $this->assySchedulerService->verifySchedule(trim($scheduleId));
                    $results[] = $result;
                }
                
                // Check if all succeeded
                $allSuccess = collect($results)->every('success');
                
                return response()->json([
                    'success' => $allSuccess,
                    'message' => $allSuccess ? 'All schedules verified successfully' : 'Some schedules failed to verify',
                    'results' => $results
                ], $allSuccess ? 200 : 400);
            } else {
                // Single verification
                $result = $this->assySchedulerService->verifySchedule($id);
                return response()->json($result, $result['success'] ? 200 : 400);
            }
        } catch (\Exception $e) {
            Log::error("Schedule verification error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete schedules
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'conveyor_id' => 'nullable|exists:master_conveyor,id',
        ]);

        try {
            $result = $this->assySchedulerService->deleteSchedules(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('conveyor_id')
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error("Schedule deletion error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get manage data for a specific conveyor and date
     */
    public function manageData(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'date' => 'required|date',
        ]);

        try {
            $result = $this->assySchedulerService->getManageData(
                $request->input('conveyor_id'),
                $request->input('date')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Manage data retrieval error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load manage data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save manage changes
     */
    public function saveManage(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'date' => 'required|date',
            'shifts' => 'required|array',
        ]);

        try {
            $result = $this->assySchedulerService->saveManageData(
                $request->input('conveyor_id'),
                $request->input('date'),
                $request->input('shifts')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Manage save error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save manage changes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available assy data with date range and pagination
     */
    public function availableAssyData(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'selected_date' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'page' => 'nullable|integer|min:1',
        ]);

        // Validate 7-day maximum range
        // if ($request->filled('start_date') && $request->filled('end_date')) {
        //     $start = Carbon::parse($request->input('start_date'));
        //     $end = Carbon::parse($request->input('end_date'));
        //     if ($start->diffInDays($end) > 7) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Date range cannot exceed 7 days'
        //         ], 400);
        //     }
        // }

        try {
            $result = $this->assySchedulerService->getAvailableAssyData(
                $request->input('conveyor_id'),
                $request->input('selected_date'),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('page', 1)
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Available assy data retrieval error", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load available assy data: ' . $e->getMessage()
            ], 500);
        }
    }
}
