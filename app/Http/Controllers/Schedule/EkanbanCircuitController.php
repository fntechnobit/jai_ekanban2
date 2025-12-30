<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Models\MasterMachine;
use App\Models\AssySchedule;
use App\Services\EkanbanCircuitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Picqer\Barcode\BarcodeGeneratorPNG;
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
                // Generate QR Code for barcode_kanban
                $qrText = !empty($circuit->barcode_kanban) ? $circuit->barcode_kanban : $circuit->cct_no;
                if (!empty($qrText)) {
                    try {
                        $options = new QROptions([
                            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                            'scale' => 5,
                            'imageTransparent' => false
                        ]);
                        $qrcode = new QRCode($options);
                        $qrCodeDataUri = $qrcode->render($qrText);
                        
                        // Extract base64 data from data URI
                        $qrCodeData = explode(',', $qrCodeDataUri)[1];
                        $qrCodeBinary = base64_decode($qrCodeData);
                        
                        $qrPath = 'temp/qr_' . $circuit->id . '_' . time() . rand(1000, 9999) . '.png';
                        Storage::disk('public')->put($qrPath, $qrCodeBinary);
                        $circuit->qr_code_path = '/storage/' . $qrPath;
                    } catch (\Exception $e) {
                        Log::error('QR generation failed for circuit ' . $circuit->id . ': ' . $e->getMessage());
                    }
                }

                // Generate Barcode for machine
                $barcodeData = $circuit->machine ?? $circuit->cct_code ?? '';
                if (!empty($barcodeData)) {
                    try {
                        $generator = new BarcodeGeneratorPNG();
                        $barcode = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128, 3, 80);
                        $barcodePath = 'temp/barcode_' . $circuit->id . '_' . time() . rand(1000, 9999) . '.png';
                        Storage::disk('public')->put($barcodePath, $barcode);
                        $circuit->barcode_path = '/storage/' . $barcodePath;
                    } catch (\Exception $e) {
                        Log::error('Barcode generation failed for circuit ' . $circuit->id . ': ' . $e->getMessage());
                    }
                }
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
            
            // Generate QR Code for barcode_kanban
            $qrText = !empty($circuit->barcode_kanban) ? $circuit->barcode_kanban : $circuit->cct_no;
            if (!empty($qrText)) {
                try {
                    $options = new QROptions([
                        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                        'scale' => 5,
                        'imageTransparent' => false
                    ]);
                    $qrcode = new QRCode($options);
                    $qrCodeDataUri = $qrcode->render($qrText);
                    
                    // Extract base64 data from data URI
                    $qrCodeData = explode(',', $qrCodeDataUri)[1];
                    $qrCodeBinary = base64_decode($qrCodeData);
                    
                    $qrPath = 'temp/qr_' . $circuit->id . '_' . time() . rand(1000, 9999) . '.png';
                    Storage::disk('public')->put($qrPath, $qrCodeBinary);
                    $circuit->qr_code_path = '/storage/' . $qrPath;
                    Log::info('QR code generated successfully: ' . $circuit->qr_code_path);
                } catch (\Exception $e) {
                    Log::error('QR generation failed for circuit ' . $circuit->id . ': ' . $e->getMessage());
                }
            }

            // Generate Barcode for machine (using cct_code or machine field)
            $barcodeData = $circuit->barcode_mesin ?? '';
            if (!empty($barcodeData)) {
                try {
                    $generator = new BarcodeGeneratorPNG();
                    $barcode = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128, 3, 80);
                    $barcodePath = 'temp/barcode_' . $circuit->id . '_' . time() . rand(1000, 9999) . '.png';
                    Storage::disk('public')->put($barcodePath, $barcode);
                    $circuit->barcode_path = '/storage/' . $barcodePath;
                    Log::info('Barcode generated successfully: ' . $circuit->barcode_path);
                } catch (\Exception $e) {
                    Log::error('Barcode generation failed for circuit ' . $circuit->id . ': ' . $e->getMessage());
                }
            }
        }

        // Mark circuits as printed
        $this->ekanbanCircuitService->markAsPrinted($ids, Auth::id());

        $html = view('schedule.ekanban_circuit.print_ticket', compact('circuits'))->render();

        return response()->json([
            'ok' => true,
            'html' => $html
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