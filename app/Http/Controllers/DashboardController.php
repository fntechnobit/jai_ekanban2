<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    /**
     * Constructor
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the dashboard.
     */
    public function index()
    {
        $printingStats = $this->dashboardService->getPrintingStats();
        $scheduleOverview = $this->dashboardService->getScheduleOverview();
        $recentActivity = $this->dashboardService->getRecentActivity(10);

        return view('dashboard.index', compact(
            'printingStats',
            'scheduleOverview',
            'recentActivity'
        ));
    }

    /**
     * Get printing trend data for chart (AJAX endpoint)
     */
    public function getPrintingTrendData()
    {
        $printingTrend = $this->dashboardService->getPrintingTrend();
        return response()->json($printingTrend);
    }
}
