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
            
            $circuits = $this->ekanbanCircuitService->getCircuitsForPrint($ids);

            // Generate QR codes and barcodes for each circuit
            foreach ($circuits as $circuit) {
                BarcodeHelper::generateCircuitBarcodes($circuit, 'barcode_kanban', 'cct_no', 'barcode_mesin', 'cct_code');
            }

            $html = view('schedule.ekanban_circuit.print_ticket', compact('circuits'))->render();
            
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
        
        $circuits = $this->ekanbanCircuitService->getCircuitsForPrint($ids);
        
        Log::info('Print function called with ' . count($circuits) . ' circuits');

        // Generate QR codes and barcodes for each circuit
        foreach ($circuits as $circuit) {
            Log::info('Processing circuit ID: ' . $circuit->id . ', CCT NO: ' . ($circuit->cct_no ?? 'N/A') . ', Barcode Kanban: ' . ($circuit->barcode_kanban ?? 'N/A'));
            BarcodeHelper::generateCircuitBarcodes($circuit);
        }

        // Mark circuits as printed
        $this->ekanbanCircuitService->markAsPrinted($ids, Auth::id());

        $html = view('schedule.ekanban_circuit.print_ticket', compact('circuits'))->render();

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
}