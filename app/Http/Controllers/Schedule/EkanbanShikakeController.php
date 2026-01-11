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

            // Render process-specific templates
            $html = $this->renderPrintTickets($shikakes);
            
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
     * Print individual shikake - routes to process-specific templates
     */
    public function print(Request $request)
    {
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
        
        $shikakes = $this->ekanbanShikakeService->getShikakesForPrint($ids);

        // Render process-specific templates
        $html = $this->renderPrintTickets($shikakes);

        // Mark shikakes as printed
        $this->ekanbanShikakeService->markAsPrinted($ids, Auth::id());

        return response()->json([
            'ok' => true,
            'html' => $html
        ]);
    }

    /**
     * Render print tickets for shikakes - each process type has its own standalone template
     */
    private function renderPrintTickets($shikakes)
    {
        $htmlParts = [];
        
        foreach ($shikakes as $shikake) {
            $process = $shikake->process ?? null;
            $processData = $shikake->details ?? null;
            
            // Generate barcodes/QR codes based on process type
            $this->generateShikakeBarcodes($shikake, $processData, $process);
            
            $templateMap = [
                'TWIST' => 'schedule.ekanban_shikake.print_ticket_twist',
                'BONDER' => 'schedule.ekanban_shikake.print_ticket_bonder',
                'JOINT' => 'schedule.ekanban_shikake.print_ticket_joint',
                'SHIELD' => 'schedule.ekanban_shikake.print_ticket_shield',
                'DBL CRIMP' => 'schedule.ekanban_shikake.print_ticket_dbl_crimp',
            ];
            
            $template = $templateMap[$process] ?? 'schedule.ekanban_shikake.print_ticket_generic';
            
            $htmlParts[] = view($template, [
                'shikake' => $shikake,
                'processData' => $processData
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