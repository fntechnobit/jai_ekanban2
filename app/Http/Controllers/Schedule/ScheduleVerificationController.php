<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\KanbanBalanceCircuit;
use App\Models\KanbanBalanceShikake;
use App\Models\MasterConveyor;
use App\Services\ScheduleVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                return $schedule->conveyor_name ?? '-';
            })
            ->addColumn('dates', function ($schedule) {
                return Carbon::parse($schedule->schedule_date)->format('Y-m-d');
            })
            ->addColumn('shift_name', function ($schedule) {
                return 'Shift ' . $schedule->shift;
            })
            ->addColumn('capacity', function ($schedule) {
                return $schedule->capacity ?? 0;
            })
            ->addColumn('listing', function ($schedule) {
                return $schedule->has_assy ? $schedule->total_listing : 0;
            })
            ->addColumn('assy', function ($schedule) {
                return $schedule->has_assy ? ($schedule->assy_list ?: '-') : '-';
            })
            ->addColumn('status', function ($schedule) {
                if (!$schedule->has_assy) {
                    return '<span class="badge bg-secondary">No Data</span>';
                }
                if ($schedule->is_lock == 1) {
                    return '<span class="badge bg-success">Verified</span>';
                }
                return '<span class="badge bg-danger">Pending</span>';
            })
            ->addColumn('action', function ($schedule) {
                if ($schedule->has_assy && $schedule->is_lock == 1) {
                    // Verified — show Detail + Unverify
                    return '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-soft-info btn-sm btn-detail" 
                            data-conveyor-id="' . $schedule->conveyor_id . '" 
                            data-date="' . $schedule->schedule_date . '" 
                            data-shift="' . $schedule->shift . '">
                            <i class="ti ti-eye"></i> Detail
                        </button>
                        <button type="button" class="btn btn-soft-warning btn-sm btn-unverify" 
                            data-conveyor-id="' . $schedule->conveyor_id . '" 
                            data-date="' . $schedule->schedule_date . '" 
                            data-shift="' . $schedule->shift . '">
                            <i class="ti ti-lock-open"></i> Unverify
                        </button>
                    </div>';
                }
                // Pending OR No Data — show Verify button (allows opening modal to drag-in from other dates)
                return '<div class="btn-group" role="group">
                    <button type="button" class="btn btn-soft-success btn-sm btn-verify" 
                        data-conveyor-id="' . $schedule->conveyor_id . '" 
                        data-date="' . $schedule->schedule_date . '" 
                        data-shift="' . $schedule->shift . '">
                        <i class="ti ti-check"></i> Verify
                    </button>
                </div>';
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
     * Get available dates (H to H+10) that have schedules for a conveyor
     */
    public function availableDates(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $currentDate = $request->input('current_date');
        $currentShift = $request->input('current_shift');
        $daysRange = $request->input('days_range', 10);

        $result = $this->scheduleVerificationService->getAvailableDates(
            $conveyorId, $currentDate, $currentShift, $daysRange
        );

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

    /**
     * Reset all kanban balance (sisa & nomor_urut) to zero
     */
    public function resetBalance(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:RESET SEMUA BALANCE',
            'conveyor_id' => 'nullable|integer|exists:master_conveyor,id',
        ]);

        try {
            DB::beginTransaction();

            $conveyorId = $request->input('conveyor_id');

            if ($conveyorId) {
                // Reset only for the selected conveyor
                $circuitCount = KanbanBalanceCircuit::where('conveyor_id', $conveyorId)->count();
                $shikakeCount = KanbanBalanceShikake::where('conveyor_id', $conveyorId)->count();

                KanbanBalanceCircuit::where('conveyor_id', $conveyorId)->update(['sisa' => 0, 'last_nomor_urut' => 0]);
                KanbanBalanceShikake::where('conveyor_id', $conveyorId)->update(['sisa' => 0, 'last_nomor_urut' => 0]);

                $conveyor = MasterConveyor::find($conveyorId);
                $conveyorName = $conveyor ? $conveyor->conveyor : $conveyorId;

                DB::commit();

                Log::warning('KANBAN BALANCE RESET: Balance reset to 0 for conveyor ' . $conveyorName . ' by user ' . auth()->id(), [
                    'conveyor_id' => $conveyorId,
                    'circuit_records' => $circuitCount,
                    'shikake_records' => $shikakeCount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Reset berhasil untuk conveyor {$conveyorName}. {$circuitCount} record circuit dan {$shikakeCount} record shikake di-reset ke 0.",
                ]);
            } else {
                // Reset all
                $circuitCount = KanbanBalanceCircuit::count();
                $shikakeCount = KanbanBalanceShikake::count();

                KanbanBalanceCircuit::query()->update(['sisa' => 0, 'last_nomor_urut' => 0]);
                KanbanBalanceShikake::query()->update(['sisa' => 0, 'last_nomor_urut' => 0]);

                DB::commit();

                Log::warning('KANBAN BALANCE RESET: All balance reset to 0 by user ' . auth()->id(), [
                    'circuit_records' => $circuitCount,
                    'shikake_records' => $shikakeCount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Reset berhasil. {$circuitCount} record circuit dan {$shikakeCount} record shikake di-reset ke 0.",
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('KANBAN BALANCE RESET FAILED: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Reset gagal: ' . $e->getMessage(),
            ], 500);
        }
    }
}
