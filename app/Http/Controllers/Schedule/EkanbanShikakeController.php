<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Models\MasterMachine;
use App\Models\AssySchedule;
use App\Enums\ProcessType;
use App\Services\EkanbanShikakeService;
use App\Helpers\BarcodeHelper;
use App\Helpers\ExcelExportHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EkanbanShikakeController extends Controller
{
    /**
     * Tinggi barcode Code 39 (dot) per cell tiket BONDER. Tinggi tiket terkunci
     * 576px (lebar kepala cetak), jadi nilai ini diukur lewat raster capture agar
     * tidak ada baris yang terdorong keluar.
     */
    private const NAVIGASI_BARCODE_HEIGHT = 88;
    private const PROCESS_BARCODE_HEIGHT = 88;

    protected $ekanbanShikakeService;

    public function __construct(EkanbanShikakeService $ekanbanShikakeService)
    {
        $this->ekanbanShikakeService = $ekanbanShikakeService;
    }
    /**
     * Show print per machine page
     */
    public function printMachine(Request $request)
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        $machines = MasterMachine::orderBy('machine')->get();
        $processTypes = ProcessType::cases();

        if ($request->ajax()) {
            try {
                $data = $this->ekanbanShikakeService->getShikakeDataForTable($request);
                return response()->json($data);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('EkanbanShikake DataTable error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'filters' => $request->only(['machine', 'date', 'shift', 'cutoff', 'process', 'print_status']),
                ]);
                return response()->json([
                    'draw' => intval($request->input('draw', 1)),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Gagal memuat data. Silakan coba lagi atau hubungi administrator.',
                ]);
            }
        }

        return view('schedule.ekanban_shikake.print_machine', compact('areas', 'conveyors', 'machines', 'processTypes'));
    }

    /**
     * Show print preview page - for AJAX returns preview HTML
     */
    public function printPreview(Request $request)
    {
        // If ids parameter is provided, return preview HTML for AJAX request
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);

            $shikakes = $this->ekanbanShikakeService->getShikakesForPrint($ids);

            Log::info('EkanbanShikake printPreview', [
                'group_ids' => $ids,
                'group_count' => count($ids),
                'shikakes_fetched' => $shikakes->count(),
            ]);

            // Support mode parameter: 'print' for thermal (120mm), 'preview' for screen (576px)
            $mode = $request->input('mode', 'preview');
            $html = $this->renderPrintTickets($shikakes, $mode);

            return response($html);
        }
        
        // Otherwise, show the full preview page with filters
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        $machines = MasterMachine::orderBy('machine')->get();
        $processTypes = ProcessType::cases();

        if ($request->ajax()) {
            try {
                $data = $this->ekanbanShikakeService->getShikakeDataForTable($request);
                return response()->json($data);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('EkanbanShikake PrintPreview DataTable error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'filters' => $request->only(['machine', 'date', 'shift', 'cutoff', 'process', 'print_status']),
                ]);
                return response()->json([
                    'draw' => intval($request->input('draw', 1)),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Gagal memuat data. Silakan coba lagi atau hubungi administrator.',
                ]);
            }
        }

        return view('schedule.ekanban_shikake.print_preview', compact('areas', 'conveyors', 'machines', 'processTypes'));
    }

    /**
     * Print individual shikake - routes to process-specific templates / mark as printed
     */
    public function print(Request $request)
    {
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);

        // Only admins may reprint a kanban that has already been printed before
        if (!Auth::user()->isAdmin() && $this->ekanbanShikakeService->anyAlreadyPrinted($ids)) {
            return response()->json([
                'ok' => false,
                'message' => 'Hanya admin yang dapat mencetak ulang kanban yang sudah pernah diprint.'
            ], 403);
        }

        // Progressive print rule - a cut off stays locked until every earlier
        // cut off on the same machine/shift/date has been fully printed.
        $cutoffViolation = $this->ekanbanShikakeService->getCutoffOrderViolation($ids);
        if ($cutoffViolation) {
            return response()->json([
                'ok' => false,
                'message' => $cutoffViolation
            ], 422);
        }

        // If check_only flag is set, this is just an authorization pre-check
        // (used by the client before running the physical QZ Tray print job)
        if ($request->boolean('check_only')) {
            return response()->json(['ok' => true]);
        }

        // Mark shikakes as printed
        $this->ekanbanShikakeService->markAsPrinted($ids, Auth::id());

        // If mark_only flag is set, skip HTML generation
        if ($request->boolean('mark_only')) {
            return response()->json(['ok' => true]);
        }

        $shikakes = $this->ekanbanShikakeService->getShikakesForPrint($ids);

        // Render process-specific templates in PRINT mode (120mm x 70mm for thermal)
        $html = $this->renderPrintTickets($shikakes, 'print');

        return response()->json([
            'ok' => true,
            'html' => $html
        ]);
    }

    /**
     * Render print tickets for shikakes - each process type has its own standalone template
     * @param Collection $shikakes - Shikake records to render
     * @param string $mode - 'preview' for screen display (576px), 'print' for thermal printer (120mm)
     */
    private function renderPrintTickets($shikakes, $mode = 'print')
    {
        $htmlParts = [];
        $suffix = $mode === 'preview' ? '_preview' : '_print';
        
        foreach ($shikakes as $shikake) {
            $process = $shikake->process ?? null;
            $processData = $shikake->details ?? null;
            
            // Generate barcodes/QR codes based on process type
            $this->generateShikakeBarcodes($shikake, $processData, $process);
            
            // Template map with mode suffix (_preview or _print)
            // Note: TWIST belongs to circuit/cutting module, not shikake
            $templateMap = [
                'BONDER' => 'schedule.ekanban_shikake.print_ticket_bonder' . $suffix,
                'JOINT' => 'schedule.ekanban_shikake.print_ticket_joint' . $suffix,
                'SHIELD' => 'schedule.ekanban_shikake.print_ticket_shield' . $suffix,
                'DBL CRIMP' => 'schedule.ekanban_shikake.print_ticket_dbl_crimp' . $suffix,
            ];
            
            $template = $templateMap[$process] ?? 'schedule.ekanban_shikake.print_ticket_generic';
            
            $htmlParts[] = view($template, [
                'shikake' => $shikake,
                'processData' => $processData,
                'mode' => $mode
            ])->render();
        }
        
        return '<div id="print_stack_ajax">' . implode('', $htmlParts) . '</div>';
    }

    /**
     * Generate barcodes and QR codes for shikake based on process type
     */
    private function generateShikakeBarcodes($shikake, $processData, $process)
    {
        // QR code for barcode_kanban (common for all processes)
        if (!empty($shikake->barcode_kanban)) {
            $shikake->qr_code_path = BarcodeHelper::generateQRCodeCached($shikake->barcode_kanban, 'shikake');
        }
        
        // Process-specific barcodes
        if ($processData) {
            // QR code for barcode_shikake
            if (!empty($processData->barcode_shikake)) {
                $processData->barcode_shikake_path = BarcodeHelper::generateQRCodeCached($processData->barcode_shikake, 'shikake');
            }

            // QR code for qrcode_drawing (drawing QR - common to all processes)
            if (!empty($processData->qrcode_drawing)) {
                $processData->qrcode_drawing_path = BarcodeHelper::generateQRCodeCached($processData->qrcode_drawing, 'shikake');
            }

            // Barcode 1D memakai Code 39 dengan bar sempit 3 dot / lebar 7 dot
            // (lihat BarcodeHelper::CODE39_NARROW). Bar 3 dot tahan dot bleed printer
            // thermal; bar 2 dot membuat celah tertutup, bar menyatu, gagal discan.
            //
            // PENTING - PNG WAJIB tampil 1:1 (tanpa width/max-width/height CSS yang
            // berbeda dari ukuran aslinya). Barcode 1D yang diperkecil membuat bar
            // berdekatan melebur saat di-threshold hitam-putih untuk thermal 203dpi -
            // itu penyebab barcode navigasi dulu selalu gagal discan. Cell bonder
            // memakai table-layout:auto sehingga lebarnya mengikuti barcode
            // (terpanjang "B-AK172.A" = 459px).
            if (!empty($processData->barcode_navigasi)) {
                $processData->barcode_navigasi_path = BarcodeHelper::generateCode39Cached($processData->barcode_navigasi, self::NAVIGASI_BARCODE_HEIGHT, 'shikake');
                $processData->barcode_navigasi_data = BarcodeHelper::sanitizeCode39($processData->barcode_navigasi);
            }

            // Barcode for barcode_process (middle-right)
            if (!empty($processData->barcode_process)) {
                $processData->barcode_process_path = BarcodeHelper::generateCode39Cached($processData->barcode_process, self::PROCESS_BARCODE_HEIGHT, 'shikake');
                $processData->barcode_process_data = BarcodeHelper::sanitizeCode39($processData->barcode_process);
            }
        }

        // Barcode for barcode_mesin (DBL CRIMP specific)
        if ($process === 'DBL CRIMP' && $processData && !empty($processData->barcode_mesin)) {
            $processData->barcode_mesin_path = BarcodeHelper::generateCode39Cached($processData->barcode_mesin, self::PROCESS_BARCODE_HEIGHT, 'shikake');
        }

    }

    /**
     * Get machines by area for dynamic filtering
     */
    public function getMachinesByConveyor(Request $request)
    {
        // Area alone is not enough - the machine list is scoped by the selected
        // process as well, so only machines that actually run that process in
        // the area are offered.
        $machines = $this->ekanbanShikakeService->getMachineOptions(
            $request->get('area_id'),
            $request->get('process')
        );

        // Format for select dropdown
        $formattedMachines = $machines->map(function($machine) {
            return [
                'machine' => $machine,
                'name' => $machine
            ];
        });

        return response()->json($formattedMachines);
    }

    /**
     * Print history page - list of kanban that has already been printed,
     * with the print date and machine on top of the print list columns.
     */
    public function history(Request $request)
    {
        if ($request->ajax()) {
            try {
                return response()->json($this->ekanbanShikakeService->getPrintHistoryForTable($request));
            } catch (\Exception $e) {
                Log::error('EkanbanShikake history DataTable error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'filters' => $request->only(['machine', 'date_start', 'date_end', 'shift', 'cutoff', 'process', 'area_id']),
                ]);
                return response()->json([
                    'draw' => intval($request->input('draw', 1)),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Gagal memuat data. Silakan coba lagi atau hubungi administrator.',
                ]);
            }
        }

        $areas = MasterArea::orderBy('area')->get();
        $processTypes = ProcessType::cases();

        return view('schedule.ekanban_shikake.history', compact('areas', 'processTypes'));
    }

    /**
     * Export the print history (same filters as the screen) to Excel
     */
    public function historyExport(Request $request)
    {
        $rows = $this->ekanbanShikakeService->getPrintHistoryRows($request);

        $headers = [
            'No', 'Process', 'Code', 'Conveyor', 'Family', 'Qty', 'Issue', 'Seq',
            'Kanban', 'Machine', 'Tgl Schedule', 'Shift', 'Cut Off', 'Tgl Print',
            'Diprint Oleh', 'Jml Print',
        ];

        $data = $rows->map(function ($row) {
            return [
                $row['DT_RowIndex'],
                $row['process'],
                $row['identifier'],
                $row['conveyor'],
                $row['family'],
                $row['qty'],
                $row['issue_count'],
                $row['sequence'],
                $row['barcodes'],
                $row['machine'],
                $row['date'],
                $row['shift'],
                $row['cutoff'],
                $row['printed_at'],
                $row['printed_by'],
                $row['print_count'],
            ];
        })->all();

        return ExcelExportHelper::download(
            'History Print Shikake',
            $headers,
            $data,
            'history_print_shikake_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
