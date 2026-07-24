<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Services\BalanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BalanceReportController extends Controller
{
    protected BalanceReportService $reportService;

    public function __construct(BalanceReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Daily balance history report (screen).
     */
    public function index(Request $request)
    {
        $date       = $request->input('date', Carbon::today()->toDateString());
        $conveyorId = $request->input('conveyor_id') ?: null;
        $type       = $request->input('type', 'all');

        $areas     = MasterArea::whereNull('deleted_at')->orderBy('area')->get();
        $conveyors = MasterConveyor::whereNull('deleted_at')->orderBy('conveyor')->get();

        $report = $this->reportService->buildRows($date, $conveyorId ? (int) $conveyorId : null, $type);

        return view('report.balance_history', [
            'areas'       => $areas,
            'conveyors'   => $conveyors,
            'date'        => $report['date'],
            'prevDate'    => $report['prev_date'],
            'conveyorId'  => $conveyorId,
            'type'        => $type,
            'rows'        => $report['rows'],
            'totals'      => $report['totals'],
        ]);
    }

    /**
     * Export the same report as CSV (streamed, no extra dependency).
     */
    public function export(Request $request): StreamedResponse
    {
        $date       = $request->input('date', Carbon::today()->toDateString());
        $conveyorId = $request->input('conveyor_id') ?: null;
        $type       = $request->input('type', 'all');

        $report   = $this->reportService->buildRows($date, $conveyorId ? (int) $conveyorId : null, $type);
        $rows     = $report['rows'];
        $totals   = $report['totals'];
        $prevDate = $report['prev_date'];

        $filename = 'balance_history_' . $report['date'] . '.csv';

        return response()->streamDownload(function () use ($rows, $totals, $report, $prevDate) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens it correctly
            fwrite($out, "\xEF\xBB\xBF");

            // Report meta
            fputcsv($out, ['Balance History Report']);
            fputcsv($out, ['Tanggal', $report['date']]);
            fputcsv($out, ['Sisa H-1 (kolom)', 'Sisa akhir tanggal ' . $prevDate]);
            fputcsv($out, []);

            // Header
            fputcsv($out, [
                'Conveyor',
                'Tipe',
                'Kode',
                'Sisa H-1',
                'Kebutuhan Listing',
                'Kanban (Produced)',
                'Add Cutting',
                'Defect',
                'Sisa Hari Ini',
                'Cek',
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['conveyor_name'],
                    $r['type_label'],
                    $r['code'],
                    $r['sisa_h1'],
                    $r['kebutuhan'],
                    $r['produced'],
                    $r['add'],
                    $r['defect'],
                    $r['sisa_today'],
                    $r['check'],
                ]);
            }

            // Totals
            fputcsv($out, [
                'TOTAL', '', '',
                $totals['sisa_h1'],
                $totals['kebutuhan'],
                $totals['produced'],
                $totals['add'],
                $totals['defect'],
                $totals['sisa_today'],
                $totals['check'],
            ]);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
