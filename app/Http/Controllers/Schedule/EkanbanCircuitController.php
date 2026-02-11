<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Helpers\BarcodeHelper;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Models\MasterMachine;
use App\Models\AssySchedule;
use App\Models\MasterCircuit;
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
            $data = $this->ekanbanCircuitService->getCircuitDataForTable($request);
            return response()->json($data);
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

            $allCircuits = $this->ekanbanCircuitService->getCircuitsForPrint($ids);

            // Separate by type
            $cuttingCircuits = $allCircuits->filter(fn($c) => ($c->type ?? MasterCircuit::TYPE_CUTTING) === MasterCircuit::TYPE_CUTTING);
            $twistCircuits = $allCircuits->filter(fn($c) => ($c->type ?? null) === MasterCircuit::TYPE_CUTTING_TWIST);

            // Generate barcodes for CUTTING circuits
            foreach ($cuttingCircuits as $circuit) {
                BarcodeHelper::generateCircuitBarcodes($circuit, 'barcode_kanban', 'cct_no', 'barcode_mesin', 'cct_code');
            }

            // Generate barcodes for CUTTING_TWIST circuits
            foreach ($twistCircuits as $circuit) {
                $this->generateTwistBarcodes($circuit);
            }

            $html = '';
            if ($cuttingCircuits->isNotEmpty()) {
                $circuits = $cuttingCircuits;
                $html .= view('schedule.ekanban_circuit.print_ticket', compact('circuits'))->render();
            }
            if ($twistCircuits->isNotEmpty()) {
                $circuits = $twistCircuits;
                $html .= view('schedule.ekanban_circuit.print_ticket_twist', compact('circuits'))->render();
            }

            return response($html);
        }
        
        // Otherwise, show the full preview page with filters
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        $machines = MasterMachine::orderBy('machine')->get();

        if ($request->ajax()) {
            $data = $this->ekanbanCircuitService->getCircuitDataForTable($request);
            return response()->json($data);
        }

        return view('schedule.ekanban_circuit.print_preview', compact('areas', 'conveyors', 'machines'));
    }

    /**
     * Print individual circuit
     */
    public function print(Request $request)
    {
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);

        $allCircuits = $this->ekanbanCircuitService->getCircuitsForPrint($ids);

        Log::info('Print function called with ' . count($allCircuits) . ' circuits');

        // Separate by type
        $cuttingCircuits = $allCircuits->filter(fn($c) => ($c->type ?? MasterCircuit::TYPE_CUTTING) === MasterCircuit::TYPE_CUTTING);
        $twistCircuits = $allCircuits->filter(fn($c) => ($c->type ?? null) === MasterCircuit::TYPE_CUTTING_TWIST);

        // Generate barcodes for CUTTING circuits
        foreach ($cuttingCircuits as $circuit) {
            Log::info('Processing circuit ID: ' . $circuit->id . ', CCT NO: ' . ($circuit->cct_no ?? 'N/A') . ', Barcode Kanban: ' . ($circuit->barcode_kanban ?? 'N/A'));
            BarcodeHelper::generateCircuitBarcodes($circuit);
        }

        // Generate barcodes for CUTTING_TWIST circuits
        foreach ($twistCircuits as $circuit) {
            Log::info('Processing twist circuit ID: ' . $circuit->id . ', CCT NO: ' . ($circuit->cct_no ?? 'N/A') . ', Barcode Kanban: ' . ($circuit->barcode_kanban ?? 'N/A'));
            $this->generateTwistBarcodes($circuit);
        }

        // Mark circuits as printed
        $this->ekanbanCircuitService->markAsPrinted($ids, Auth::id());

        $html = '';
        if ($cuttingCircuits->isNotEmpty()) {
            $circuits = $cuttingCircuits;
            $html .= view('schedule.ekanban_circuit.print_ticket', compact('circuits'))->render();
        }
        if ($twistCircuits->isNotEmpty()) {
            $circuits = $twistCircuits;
            $html .= view('schedule.ekanban_circuit.print_ticket_twist', compact('circuits'))->render();
        }

        return response()->json([
            'ok' => true,
            'html' => '<div id="print_stack_ajax">' . $html . '</div>'
        ]);
    }

    /**
     * Get all machines for dropdown (independent of conveyor)
     */
    public function getMachinesByConveyor(Request $request)
    {
        $machines = MasterMachine::orderBy('machine')->get();

        // Format for select dropdown
        $formattedMachines = $machines->map(function($machine) {
            return [
                'machine' => $machine->machine,
                'name' => $machine->machine
            ];
        });

        return response()->json($formattedMachines);
    }

    /**
     * Generate barcodes for CUTTING_TWIST circuit
     */
    private function generateTwistBarcodes($circuit)
    {
        // QR code for kanban (same as regular circuit)
        BarcodeHelper::generateCircuitBarcodes($circuit, 'barcode_kanban', 'cct_no', 'barcode_mesin', 'cct_code');

        // Barcode for barcode_navigasi
        if (!empty($circuit->barcode_navigasi)) {
            $path = BarcodeHelper::generateBarcodeCached($circuit->barcode_navigasi, null, 3, 80, 'circuit');
            if ($path) {
                $circuit->barcode_navigasi_path = $path;
            }
        }

        // Barcode for barcode_process
        if (!empty($circuit->barcode_process)) {
            $path = BarcodeHelper::generateBarcodeCached($circuit->barcode_process, null, 3, 80, 'circuit');
            if ($path) {
                $circuit->barcode_process_path = $path;
            }
        }

        // QR code for barcode_shikake
        if (!empty($circuit->barcode_shikake)) {
            $path = BarcodeHelper::generateQRCodeCached($circuit->barcode_shikake, 'circuit');
            if ($path) {
                $circuit->barcode_shikake_path = $path;
            }
        }
    }
}