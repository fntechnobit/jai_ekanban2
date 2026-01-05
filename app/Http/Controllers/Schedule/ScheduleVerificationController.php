<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\MasterConveyor;
use App\Services\ScheduleVerificationService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ScheduleVerificationController extends Controller
{
    protected $scheduleVerificationService;

    public function __construct(ScheduleVerificationService $scheduleVerificationService)
    {
        $this->scheduleVerificationService = $scheduleVerificationService;
    }

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
        $status = $request->input('status');

        $schedules = $this->scheduleVerificationService->getDatatableQuery($startDate, $endDate, $conveyorId, $status);

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
                if ($schedule->is_lock == 1) {
                    // Show Detail and Unverify buttons for verified schedules
                    return '
                        <button type="button" class="btn btn-info btn-sm btn-detail" 
                            data-conveyor-id="' . $schedule->conveyor_id . '" 
                            data-date="' . $schedule->schedule_date . '" 
                            data-shift="' . $schedule->shift . '">
                            <i class="fas fa-eye"></i> Detail
                        </button>
                        <button type="button" class="btn btn-warning btn-sm btn-unverify" 
                            data-conveyor-id="' . $schedule->conveyor_id . '" 
                            data-date="' . $schedule->schedule_date . '" 
                            data-shift="' . $schedule->shift . '">
                            <i class="fas fa-unlock"></i> Unverify
                        </button>
                    ';
                } else {
                    // Show Verify button for pending schedules
                    return '<button type="button" class="btn btn-success btn-sm btn-verify" 
                        data-conveyor-id="' . $schedule->conveyor_id . '" 
                        data-date="' . $schedule->schedule_date . '" 
                        data-shift="' . $schedule->shift . '">
                        <i class="fas fa-check"></i> Verify
                    </button>';
                }
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

        $result = $this->scheduleVerificationService->getVerificationDetails($conveyorId, $date, $shift);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    /**
     * Get available assy data for drag and drop
     */
    public function availableAssyData(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $date = $request->input('date');
        $shift = $request->input('shift');

        $result = $this->scheduleVerificationService->getAvailableAssyData($conveyorId, $date, $shift);

        return response()->json($result);
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
            'schedules' => 'nullable|array',
            'schedules.*.id' => 'required|integer',
            'schedules.*.cutoff' => 'required|integer',
            'schedules.*.qty' => 'required|integer|min:1',
            'new_items' => 'nullable|array',
            'new_items.*.assy' => 'required|string',
            'new_items.*.cutoff' => 'required|integer',
            'new_items.*.qty' => 'required|integer|min:1',
        ]);

        $result = $this->scheduleVerificationService->saveVerification(
            $request->input('conveyor_id'),
            $request->input('date'),
            $request->input('shift'),
            $request->input('schedules', []),
            $request->input('new_items', [])
        );

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * Verify a schedule - lock it for specific conveyor, date and shift
     */
    public function verify(Request $request)
    {
        $data = $request->json()->all();
        
        $request->validate([
            'conveyor_id' => 'required|integer',
            'date' => 'required|date',
            'shift' => 'required|integer',
            'cutoffs' => 'nullable|array',
        ]);

        $result = $this->scheduleVerificationService->verifySchedule(
            $data['conveyor_id'] ?? $request->input('conveyor_id'),
            $data['date'] ?? $request->input('date'),
            $data['shift'] ?? $request->input('shift'),
            $data['cutoffs'] ?? $request->input('cutoffs', [])
        );

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * Unverify a schedule - unlock it for specific conveyor, date and shift
     */
    public function unverify(Request $request)
    {
        $request->validate([
            'conveyor_id' => 'required|integer',
            'date' => 'required|date',
            'shift' => 'required|integer',
        ]);

        $result = $this->scheduleVerificationService->unverifySchedule(
            $request->input('conveyor_id'),
            $request->input('date'),
            $request->input('shift')
        );

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }
}
