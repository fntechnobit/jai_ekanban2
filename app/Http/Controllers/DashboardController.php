<?php

namespace App\Http\Controllers;

use App\Models\AssySchedule;
use App\Models\ListingStage;
use App\Models\MasterConveyor;
use App\Services\AssySchedulerService;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Kunci per rentang: satu generate untuk rentang yang sama pada satu waktu.
        // Sebelumnya setiap pembukaan dashboard memicu proses penuh, sehingga
        // beberapa proses berat berjalan bersamaan di atas tabel yang sama dan
        // saling memperlambat sampai timeout.
        $scope = sprintf(
            'dashboard-generate:%s:%s:%s',
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('conveyor_id') ?: 'all'
        );

        // Pemanggilan otomatis saat dashboard dibuka boleh dilewati bila rentang
        // yang sama baru saja selesai. Penekanan tombol manual selalu dijalankan.
        $throttle = (int) config('sirep.generate.auto_throttle_seconds', 300);

        if ($request->boolean('auto') && $throttle > 0) {
            $recent = Cache::get($scope . ':done');

            if ($recent) {
                return $this->skippedResponse(
                    'Jadwal untuk rentang ini baru saja disinkronkan pukul ' . $recent['at']
                    . ' (' . $recent['generated'] . ' schedule). Sinkronisasi otomatis dilewati.',
                    $recent['generated']
                );
            }
        }

        $lock = Cache::lock($scope . ':lock', (int) config('sirep.generate.lock_seconds', 600));

        if (!$lock->get()) {
            return $this->skippedResponse(
                'Sinkron & generate untuk rentang ini sedang berjalan. Tunggu sampai proses tersebut selesai.'
            );
        }

        try {
            $result = $this->assySchedulerService->generateSchedules(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('conveyor_id')
            );

            if ($result['success']) {
                if ($throttle > 0) {
                    Cache::put($scope . ':done', [
                        'at'        => now()->format('H:i:s'),
                        'generated' => $result['generated'],
                    ], $throttle);
                }

                return response()->json([
                    'success'     => true,
                    'skipped'     => false,
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
        } finally {
            $lock->release();
        }
    }

    /**
     * Balasan untuk permintaan yang sengaja tidak dijalankan (sedang berjalan
     * atau hasilnya masih segar). Dikirim sebagai 200 supaya layar tidak
     * menampilkannya sebagai kegagalan mengambil data dari PPC.
     */
    private function skippedResponse(string $message, int $generated = 0)
    {
        return response()->json([
            'success'     => true,
            'skipped'     => true,
            'step_failed' => null,
            'message'     => $message,
            'data'        => ['generated' => $generated, 'sync_detail' => null],
        ]);
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
