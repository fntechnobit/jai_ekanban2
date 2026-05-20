<?php

namespace App\Http\Controllers;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use App\Services\AssySchedulerService;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $assySchedulerService;

    public function __construct(DashboardService $dashboardService, AssySchedulerService $assySchedulerService)
    {
        $this->dashboardService = $dashboardService;
        $this->assySchedulerService = $assySchedulerService;
    }

    /**
     * Display the dashboard.
     */
    public function index()
    {
        $conveyors = MasterConveyor::orderBy('conveyor', 'asc')->get();
        return view('dashboard.index', compact('conveyors'));
    }

    /**
     * Return last sync and last generate timestamps (AJAX)
     */
    public function syncStatus()
    {
        $lastSync     = ListingStage::max('synced_at');
        $lastGenerate = AssySchedule::max('created_at');

        return response()->json([
            'last_sync'     => $lastSync     ? Carbon::parse($lastSync)->format('d M Y H:i:s')     : null,
            'last_generate' => $lastGenerate ? Carbon::parse($lastGenerate)->format('d M Y H:i:s') : null,
        ]);
    }

    /**
     * Manually trigger sync + generate assy schedule (AJAX)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
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
                    'success'     => true,
                    'message'     => $result['message'],
                    'step_failed' => null,
                    'data'        => [
                        'generated'   => $result['generated'],
                        'sync_detail' => $result['sync_detail'] ?? null,
                    ],
                ]);
            }

            return response()->json([
                'success'     => false,
                'step_failed' => $result['step_failed'] ?? 'unknown',
                'message'     => $result['message'],
                'data'        => [
                    'generated'   => 0,
                    'sync_detail' => $result['sync_detail'] ?? null,
                ],
            ], 400);
        } catch (\Exception $e) {
            Log::error('Dashboard generate error', ['error' => $e->getMessage()]);

            return response()->json([
                'success'     => false,
                'step_failed' => 'unknown',
                'message'     => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data'        => ['generated' => 0, 'sync_detail' => null],
            ], 500);
        }
    }

    /**
     * Get chart data: kanban printed per machine (AJAX)
     */
    public function getChartData()
    {
        $chartData = $this->dashboardService->getChartDataPerMachine();
        return response()->json($chartData);
    }

    /**
     * Get cutting datatable data (AJAX)
     */
    public function getCuttingDatatable(Request $request)
    {
        $data = $this->dashboardService->getKanbanPerMachineCutting();

        return datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('total_printed', fn($row) => number_format($row->total_printed))
            ->editColumn('total_print_count', fn($row) => number_format($row->total_print_count))
            ->make(true);
    }

    /**
     * Get shikake datatable data (AJAX)
     */
    public function getShikakeDatatable(Request $request)
    {
        $data = $this->dashboardService->getKanbanPerMachineShikake();

        return datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('total_printed', fn($row) => number_format($row->total_printed))
            ->editColumn('total_print_count', fn($row) => number_format($row->total_print_count))
            ->make(true);
    }
}
