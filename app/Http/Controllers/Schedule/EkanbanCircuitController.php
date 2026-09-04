<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Helpers\BarcodeHelper;
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
     * Render print tickets for circuits - dispatches to type-specific templates
     * CUTTING uses print_ticket, CUTTING_TWIST uses print_ticket_twist
     */
    private function renderCircuitTickets($circuits)
    {
        // Separate circuits by type
        $cuttingCircuits = $circuits->filter(fn($c) => ($c->type ?? 'CUTTING') === 'CUTTING');
        $twistCircuits = $circuits->filter(fn($c) => ($c->type ?? 'CUTTING') === 'CUTTING_TWIST');

        $htmlParts = [];

        // Render CUTTING circuits with standard template
        if ($cuttingCircuits->isNotEmpty()) {
            foreach ($cuttingCircuits as $circuit) {
                BarcodeHelper::generateCircuitBarcodes($circuit, 'barcode_kanban', 'cct_no', 'barcode_mesin', 'cct_code');
                // Generate QR code for qrcode_shikake
                if (!empty($circuit->qrcode_shikake)) {
                    $circuit->qr_shikake_path = BarcodeHelper::generateQRCodeCached($circuit->qrcode_shikake, 'circuit');
                }
            }
            $htmlParts[] = view('schedule.ekanban_circuit.print_ticket', ['circuits' => $cuttingCircuits])->render();
        }

        // Render CUTTING_TWIST circuits with twist template
        if ($twistCircuits->isNotEmpty()) {
            foreach ($twistCircuits as $circuit) {
                $this->generateCircuitTwistBarcodes($circuit);
            }
            $htmlParts[] = view('schedule.ekanban_circuit.print_ticket_twist', ['circuits' => $twistCircuits])->render();
        }

        return '<div id="print_stack_ajax">' . implode('', $htmlParts) . '</div>';
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

        // Linear barcode for barcode_mesin (top-right)
        if (!empty($circuit->barcode_mesin)) {
            $circuit->barcode_mesin_path = BarcodeHelper::generateBarcodeCached($circuit->barcode_mesin, null, 4, 90, 'circuit');
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
            $circuit->barcode_process_path = BarcodeHelper::generateBarcodeCached($circuit->barcode_process, null, 4, 90, 'circuit');
        }

        // Linear barcode for barcode_twist (replaces barcode_process in twist print)
        if (!empty($circuit->barcode_twist)) {
            $circuit->barcode_twist_path = BarcodeHelper::generateBarcodeCached($circuit->barcode_twist, null, 4, 90, 'circuit');
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
        $areaId = $request->get('area_id');

        if (!$areaId) {
            return response()->json([]);
        }

        $machines = MasterMachine::where('master_area_id', $areaId)
            ->orderBy('machine')
            ->get();

        // Format for select dropdown
        $formattedMachines = $machines->map(function($machine) {
            return [
                'machine' => $machine->machine,
                'name' => $machine->machine
            ];
        });

        return response()->json($formattedMachines);
    }
}