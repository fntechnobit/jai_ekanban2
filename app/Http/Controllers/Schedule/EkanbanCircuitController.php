<?php

namespace App\Http\Controllers\Schedule;

use App\Enums\CircuitType;
use App\Http\Controllers\Controller;
use App\Helpers\BarcodeHelper;
use App\Helpers\ExcelExportHelper;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Models\MasterMachine;
use App\Models\AssySchedule;
use App\Services\EkanbanCircuitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EkanbanCircuitController extends Controller
{
    /**
     * Tinggi barcode Code 39 pada tiket CUTTING TWIST (dot). Lebih pendek dari
     * CODE39_HEIGHT karena cell barcode twist hanya rowspan=2 dan tinggi tiket
     * terkunci 576px (lebar kepala cetak) - diukur lewat raster capture.
     */
    private const TWIST_BARCODE_HEIGHT = 64;

    /**
     * Tinggi barcode Code 39 pada tiket JAM TWIST (dot). Dua barcode mesin bertumpuk
     * di kolom kanan (circuit 1 baris 1-3 rata atas, circuit 2 baris 4-7 rata bawah);
     * 64 dot menjaga jarak antar keduanya >= 116 dot, setara jarak antar barcode tiket twist.
     */
    private const JAM_BARCODE_HEIGHT = 64;

    protected $ekanbanCircuitService;

    public function __construct(EkanbanCircuitService $ekanbanCircuitService)
    {
        $this->ekanbanCircuitService = $ekanbanCircuitService;
    }
    /**
     * Show print per machine page
     */
    public function printMachine(Request $request)
    {
        $areas = MasterArea::orderBy('area')->get();
        $machines = MasterMachine::orderBy('machine')->get();

        if ($request->ajax()) {
            try {
                $data = $this->ekanbanCircuitService->getCircuitDataForTable($request);
                return response()->json($data);
            } catch (\Exception $e) {
                Log::error('EkanbanCircuit DataTable error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'filters' => $request->only(['machine', 'date', 'shift', 'cutoff', 'type', 'print_status']),
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

        return view('schedule.ekanban_circuit.print_machine', compact('areas', 'machines'));
    }

    /**
     * Show print preview page
     */
    public function printPreview(Request $request)
    {
        // If ids parameter is provided, return preview HTML for AJAX request
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);

            $circuits = $this->ekanbanCircuitService->getCircuitsForPrint($ids);

            Log::info('EkanbanCircuit printPreview', [
                'group_ids' => $ids,
                'group_count' => count($ids),
                'circuits_fetched' => $circuits->count(),
            ]);

            // Generate barcodes and render per-type templates
            $html = $this->renderCircuitTickets($circuits);

            return response($html);
        }
        
        // Otherwise, show the full preview page with filters
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        $machines = MasterMachine::orderBy('machine')->get();

        if ($request->ajax()) {
            try {
                $data = $this->ekanbanCircuitService->getCircuitDataForTable($request);
                return response()->json($data);
            } catch (\Exception $e) {
                Log::error('EkanbanCircuit PrintPreview DataTable error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'filters' => $request->only(['machine', 'date', 'shift', 'cutoff', 'type', 'print_status']),
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

        return view('schedule.ekanban_circuit.print_preview', compact('areas', 'conveyors', 'machines'));
    }

    /**
     * Print individual circuit / mark as printed
     */
    public function print(Request $request)
    {
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);

        // Only admins may reprint a kanban that has already been printed before
        if (!Auth::user()->isAdmin() && $this->ekanbanCircuitService->anyAlreadyPrinted($ids)) {
            return response()->json([
                'ok' => false,
                'message' => 'Hanya admin yang dapat mencetak ulang kanban yang sudah pernah diprint.'
            ], 403);
        }

        // Progressive print rule - a cut off stays locked until every earlier
        // cut off on the same machine/shift/date has been fully printed.
        $cutoffViolation = $this->ekanbanCircuitService->getCutoffOrderViolation($ids);
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

        // Mark circuits as printed
        $this->ekanbanCircuitService->markAsPrinted($ids, Auth::id());

        // If mark_only flag is set, skip HTML generation
        if ($request->boolean('mark_only')) {
            return response()->json(['ok' => true]);
        }

        $circuits = $this->ekanbanCircuitService->getCircuitsForPrint($ids);
        
        Log::info('Print function called with ' . count($circuits) . ' circuits');

        // Generate barcodes and render per-type templates
        $html = $this->renderCircuitTickets($circuits);

        return response()->json([
            'ok' => true,
            'html' => $html
        ]);
    }

    /**
     * Render print tickets for circuits - dispatches to type-specific templates:
     * CUTTING → print_ticket, CUTTING_TWIST → print_ticket_twist,
     * CUTTING_EJ → print_ticket_ej, CUTTING_JAM → print_ticket_jam.
     *
     * Tiket dirender per rangkaian type yang berurutan (bukan dikelompokkan per type),
     * supaya urutan cetak tetap sama dengan urutan daftar print walau satu mesin
     * mencetak beberapa type sekaligus (mis. CUTTING dan EJ di mesin yang sama).
     */
    private function renderCircuitTickets($circuits)
    {
        $htmlParts = [];
        $run = [];
        $runType = null;

        foreach ($circuits as $circuit) {
            $type = CircuitType::fromRaw($circuit->type);
            if ($runType !== null && $type !== $runType) {
                $htmlParts[] = $this->renderTicketRun($runType, collect($run));
                $run = [];
            }
            $runType = $type;
            $run[] = $circuit;
        }
        if ($run) {
            $htmlParts[] = $this->renderTicketRun($runType, collect($run));
        }

        return '<div id="print_stack_ajax">' . implode('', $htmlParts) . '</div>';
    }

    /**
     * Generate barcode/QR lalu render satu rangkaian tiket ber-type sama.
     */
    private function renderTicketRun(CircuitType $type, $circuits): string
    {
        foreach ($circuits as $circuit) {
            match ($type) {
                CircuitType::CUTTING => $this->generateCircuitCuttingBarcodes($circuit),
                CircuitType::CUTTING_TWIST => $this->generateCircuitTwistBarcodes($circuit),
                CircuitType::CUTTING_EJ => $this->generateCircuitEjBarcodes($circuit),
                CircuitType::CUTTING_JAM => $this->generateCircuitJamBarcodes($circuit),
            };
        }

        $view = match ($type) {
            CircuitType::CUTTING => 'schedule.ekanban_circuit.print_ticket',
            CircuitType::CUTTING_TWIST => 'schedule.ekanban_circuit.print_ticket_twist',
            CircuitType::CUTTING_EJ => 'schedule.ekanban_circuit.print_ticket_ej',
            CircuitType::CUTTING_JAM => 'schedule.ekanban_circuit.print_ticket_jam',
        };

        return view($view, ['circuits' => $circuits])->render();
    }

    /**
     * Generate barcodes for CUTTING circuits: QR kanban, Code 39 barcode mesin, QR shikake
     */
    private function generateCircuitCuttingBarcodes($circuit)
    {
        BarcodeHelper::generateCircuitBarcodes($circuit, 'barcode_kanban', 'cct_no', 'barcode_mesin', 'cct_code');
        // Generate QR code for qrcode_shikake
        if (!empty($circuit->qrcode_shikake)) {
            $circuit->qr_shikake_path = BarcodeHelper::generateQRCodeCached($circuit->qrcode_shikake, 'circuit');
        }
    }

    /**
     * Generate barcodes for CUTTING_EJ circuits: sama dengan CUTTING (QR kanban,
     * Code 39 barcode mesin) ditambah QR EJ-A / EJ-B dari master.
     */
    private function generateCircuitEjBarcodes($circuit)
    {
        BarcodeHelper::generateCircuitBarcodes($circuit, 'barcode_kanban', 'cct_no', 'barcode_mesin', 'cct_code');

        if (!empty($circuit->qrcode_ej_a)) {
            $circuit->qr_ej_a_path = BarcodeHelper::generateQRCodeCached($circuit->qrcode_ej_a, 'circuit');
        }
        if (!empty($circuit->qrcode_ej_b)) {
            $circuit->qr_ej_b_path = BarcodeHelper::generateQRCodeCached($circuit->qrcode_ej_b, 'circuit');
        }
    }

    /**
     * Generate barcodes for CUTTING_JAM circuits: QR barcode mesin (qrcode_mesin, isi dari
     * MTC), QR kanban (barcode_kanban, format barcode shikake dengan kode Twist No) dan
     * Code 39 barcode mesin cadangan untuk circuit 1 dan circuit 2.
     */
    private function generateCircuitJamBarcodes($circuit)
    {
        if (!empty($circuit->qrcode_mesin)) {
            $circuit->qr_mesin_path = BarcodeHelper::generateQRCodeCached($circuit->qrcode_mesin, 'circuit');
        }

        if (!empty($circuit->barcode_kanban)) {
            $circuit->qr_code_path = BarcodeHelper::generateQRCodeCached($circuit->barcode_kanban, 'circuit');
        }

        foreach (['barcode_mesin', 'barcode_mesin_2'] as $field) {
            if (!empty($circuit->$field)) {
                $circuit->{$field . '_path'} = BarcodeHelper::generateCode39Cached($circuit->$field, self::JAM_BARCODE_HEIGHT, 'circuit');
                $circuit->{$field . '_data'} = BarcodeHelper::sanitizeCode39($circuit->$field);
                $circuit->{$field . '_width'} = BarcodeHelper::code39Width($circuit->$field);
            }
        }
    }

    /**
     * Generate barcodes specific to CUTTING_TWIST circuits
     * Includes: QR kanban, barcode mesin (linear), QR shikake, barcode process (linear)
     */
    private function generateCircuitTwistBarcodes($circuit)
    {
        // QR code for barcode_kanban (bottom-left)
        if (!empty($circuit->barcode_kanban)) {
            $circuit->qr_code_path = BarcodeHelper::generateQRCodeCached($circuit->barcode_kanban, 'circuit');
        }

        // Linear barcodes (Code 39) - tinggi dibatasi oleh tinggi cell rowspan=2 di
        // tiket twist (tinggi tiket terkunci 576px), lebar ditampilkan 1:1.
        $twistBarcodeHeight = self::TWIST_BARCODE_HEIGHT;

        // Linear barcode for barcode_mesin (top-right)
        if (!empty($circuit->barcode_mesin)) {
            $circuit->barcode_mesin_path = BarcodeHelper::generateCode39Cached($circuit->barcode_mesin, $twistBarcodeHeight, 'circuit');
            $circuit->barcode_mesin_data = BarcodeHelper::sanitizeCode39($circuit->barcode_mesin);
            $circuit->barcode_mesin_width = BarcodeHelper::code39Width($circuit->barcode_mesin);
        }

        // QR code for barcode_shikake (static from master, bottom-right)
        if (!empty($circuit->barcode_shikake)) {
            $circuit->qr_shikake_path = BarcodeHelper::generateQRCodeCached($circuit->barcode_shikake, 'circuit');
        }

        // QR code for qrcode_shikake (generated, same format as barcode_kanban but with shikake_code)
        if (!empty($circuit->qrcode_shikake)) {
            $circuit->qr_qrcode_shikake_path = BarcodeHelper::generateQRCodeCached($circuit->qrcode_shikake, 'circuit');
        }

        // Linear barcode for barcode_process (section A/B right side)
        if (!empty($circuit->barcode_process)) {
            $circuit->barcode_process_path = BarcodeHelper::generateCode39Cached($circuit->barcode_process, $twistBarcodeHeight, 'circuit');
        }

        // Linear barcode for barcode_twist (replaces barcode_process in twist print)
        if (!empty($circuit->barcode_twist)) {
            $circuit->barcode_twist_path = BarcodeHelper::generateCode39Cached($circuit->barcode_twist, $twistBarcodeHeight, 'circuit');
            $circuit->barcode_twist_data = BarcodeHelper::sanitizeCode39($circuit->barcode_twist);
            $circuit->barcode_twist_width = BarcodeHelper::code39Width($circuit->barcode_twist);
        }

        // QR code for qrcode_drawing (replaces qrcode_shikake in twist print)
        if (!empty($circuit->qrcode_drawing)) {
            $circuit->qr_qrcode_drawing_path = BarcodeHelper::generateQRCodeCached($circuit->qrcode_drawing, 'circuit');
        }
    }

    /**
     * Get machines for dropdown, scoped to the selected area
     */
    public function getMachinesByConveyor(Request $request)
    {
        // Area alone is not enough - the machine list is scoped by the selected
        // type as well, so only machines that actually have circuits of that
        // type in the area are offered.
        $machines = $this->ekanbanCircuitService->getMachineOptions(
            $request->get('area_id'),
            $request->get('type')
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
                return response()->json($this->ekanbanCircuitService->getPrintHistoryForTable($request));
            } catch (\Exception $e) {
                Log::error('EkanbanCircuit history DataTable error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'filters' => $request->only(['machine', 'date_start', 'date_end', 'shift', 'cutoff', 'type', 'area_id']),
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

        return view('schedule.ekanban_circuit.history', compact('areas'));
    }

    /**
     * Export the print history (same filters as the screen) to Excel
     */
    public function historyExport(Request $request)
    {
        $rows = $this->ekanbanCircuitService->getPrintHistoryRows($request);

        $headers = [
            'No', 'Type', 'CCT No', 'CCT Code', 'Shikake Code', 'Conveyor', 'Store',
            'Family', 'Qty', 'Issue', 'Seq', 'Kanban', 'Machine', 'Tgl Schedule',
            'Shift', 'Cut Off', 'Tgl Print', 'Diprint Oleh', 'Jml Print',
        ];

        $data = $rows->map(function ($row) {
            return [
                $row['DT_RowIndex'],
                $row['type'],
                $row['cct_no'],
                $row['cct_code'],
                $row['shikake_code'],
                $row['conveyor'],
                $row['to_store'],
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
            'History Print Cutting',
            $headers,
            $data,
            'history_print_cutting_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
