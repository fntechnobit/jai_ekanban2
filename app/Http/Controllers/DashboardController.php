<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the dashboard.
     */
    public function index()
    {
        return view('dashboard.index');
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
