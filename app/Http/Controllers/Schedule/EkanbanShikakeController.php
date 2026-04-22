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
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EkanbanShikakeController extends Controller
{
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
            $data = $this->ekanbanShikakeService->getShikakeDataForTable($request);
            return response()->json($data);
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
            $data = $this->ekanbanShikakeService->getShikakeDataForTable($request);
            return response()->json($data);
        }

        return view('schedule.ekanban_shikake.print_preview', compact('areas', 'conveyors', 'machines', 'processTypes'));
    }

    /**
     * Print individual shikake - routes to process-specific templates / mark as printed
     */
    public function print(Request $request)
    {
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);

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
            
            // Barcode for barcode_navigasi (top-right)
            if (!empty($processData->barcode_navigasi)) {
                $processData->barcode_navigasi_path = BarcodeHelper::generateBarcodeCached($processData->barcode_navigasi, null, 2, 50, 'shikake');
            }
            
            // Barcode for barcode_process (middle-right)
            if (!empty($processData->barcode_process)) {
                $processData->barcode_process_path = BarcodeHelper::generateBarcodeCached($processData->barcode_process, null, 2, 50, 'shikake');
            }
        }

        // Barcode for barcode_mesin (DBL CRIMP specific)
        if ($process === 'DBL CRIMP' && $processData && !empty($processData->barcode_mesin)) {
            $processData->barcode_mesin_path = BarcodeHelper::generateBarcodeCached($processData->barcode_mesin, null, 2, 50, 'shikake');
        }

    }

    /**
     * Get machines by conveyor for dynamic filtering
     */
    public function getMachinesByConveyor(Request $request)
    {
        $conveyorId = $request->conveyor_id;
        
        if (!$conveyorId) {
            return response()->json([]);
        }

        $machines = DB::table('master_shikake')
            ->join('master_conveyor', 'master_shikake.conveyor_id', '=', 'master_conveyor.id')
            ->where('master_conveyor.id', $conveyorId)
            ->whereNotNull('master_shikake.machine')
            ->where('master_shikake.machine', '!=', '')
            ->select('master_shikake.machine')
            ->distinct()
            ->orderBy('master_shikake.machine')
            ->pluck('machine');

        // Format for select dropdown
        $formattedMachines = $machines->map(function($machine) {
            return [
                'machine' => $machine,
                'name' => $machine
            ];
        });

        return response()->json($formattedMachines);
    }
}