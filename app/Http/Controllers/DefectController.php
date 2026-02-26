<?php

namespace App\Http\Controllers;

use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Models\MasterShikake;
use App\Models\MasterCircuit;
use App\Models\KanbanBalanceCircuit;
use App\Models\KanbanBalanceShikake;
use App\Services\DefectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DefectController extends Controller
{
    protected DefectService $defectService;

    public function __construct(DefectService $defectService)
    {
        $this->defectService = $defectService;
    }

    // =============================================
    // DEFECT CUTTING
    // =============================================

    /**
     * Display cutting defect page with datatable
     */
    public function cuttingIndex()
    {
        $areas = MasterArea::whereNull('deleted_at')->orderBy('area')->get();
        $conveyors = MasterConveyor::whereNull('deleted_at')->orderBy('conveyor')->get();
        
        return view('defect.cutting', [
            'areas' => $areas,
            'conveyors' => $conveyors,
        ]);
    }

    /**
     * Server-side datatable for cutting circuits with balance
     */
    public function cuttingDatatable(Request $request)
    {
        return $this->defectService->getCuttingDatatable(
            $request->input('conveyor_id'),
            $request->input('type'),
            $request->input('area_id')
        );
    }

    /**
     * Store cutting defect (AJAX)
     */
    public function cuttingStore(Request $request)
    {
        $validated = $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'master_circuit_id' => 'required|exists:master_circuit,id',
            'defect_date' => 'required|date|before_or_equal:today',
            'shift' => 'required|integer|in:1,2,3',
            'qty_defect' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->defectService->recordDefect(
            'circuit',
            $validated['conveyor_id'],
            [
                'master_circuit_id' => $validated['master_circuit_id'],
            ],
            $validated['qty_defect'],
            [
                'date' => $validated['defect_date'],
                'shift' => $validated['shift'],
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get circuits for a conveyor (AJAX)
     */
    public function getCircuits(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        if (!$conveyorId) {
            return response()->json([]);
        }

        $circuits = $this->defectService->getCircuitsWithBalance($conveyorId);

        return response()->json($circuits->map(function ($balance) {
            $circuit = $balance->masterCircuit;
            return [
                'master_circuit_id' => $balance->master_circuit_id,
                'cct_no' => $circuit ? $circuit->cct_no : 'Unknown',
                'cct_code' => $circuit ? $circuit->cct_code : 'Unknown',
                'sisa' => $balance->sisa,
            ];
        }));
    }

    /**
     * Get circuit balance (AJAX)
     */
    public function getCircuitBalance(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $masterCircuitId = $request->input('master_circuit_id');

        $balance = $this->defectService->getCircuitBalance($conveyorId, $masterCircuitId);

        if (!$balance) {
            return response()->json(['error' => 'Balance not found'], 404);
        }

        return response()->json($balance);
    }

    // =============================================
    // DEFECT SHIKAKE
    // =============================================

    /**
     * Display shikake defect page with datatable
     */
    public function shikakeIndex()
    {
        $areas = MasterArea::whereNull('deleted_at')->orderBy('area')->get();
        $conveyors = MasterConveyor::whereNull('deleted_at')->orderBy('conveyor')->get();
        
        $shikakeTypes = [
            'BONDER' => 'Bonder',
            'DBL CRIMP' => 'Dbl Crimp',
            'JOINT' => 'Joint',
            'SHIELD' => 'Shield',
            'TWIST' => 'Twist',
        ];

        return view('defect.shikake', [
            'areas' => $areas,
            'conveyors' => $conveyors,
            'shikakeTypes' => $shikakeTypes,
        ]);
    }

    /**
     * Server-side datatable for shikake with balance
     */
    public function shikakeDatatable(Request $request)
    {
        return $this->defectService->getShikakeDatatable(
            $request->input('conveyor_id'),
            $request->input('process_type'),
            $request->input('area_id')
        );
    }

    /**
     * Store shikake defect (AJAX)
     */
    public function shikakeStore(Request $request)
    {
        $validated = $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'master_shikake_id' => 'required|exists:master_shikake,id',
            'shikake_type' => 'required|string',
            'defect_date' => 'required|date|before_or_equal:today',
            'shift' => 'required|integer|in:1,2,3',
            'qty_defect' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->defectService->recordDefect(
            'shikake',
            $validated['conveyor_id'],
            [
                'master_shikake_id' => $validated['master_shikake_id'],
                'shikake_type' => $validated['shikake_type'],
            ],
            $validated['qty_defect'],
            [
                'date' => $validated['defect_date'],
                'shift' => $validated['shift'],
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get shikakes for a conveyor and type (AJAX)
     */
    public function getShikakes(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $shikakeType = $request->input('shikake_type');

        if (!$conveyorId) {
            return response()->json([]);
        }

        $shikakes = $this->defectService->getShikakesWithBalance($conveyorId, $shikakeType);

        return response()->json($shikakes->map(function ($balance) {
            $shikake = $balance->masterShikake;
            $code = $shikake ? ($shikake->machine ?? "SHK-{$shikake->id}") : "Unknown";
            $process = $shikake ? $shikake->process : 'Unknown';
            
            return [
                'master_shikake_id' => $balance->master_shikake_id,
                'code' => $code,
                'process' => $process,
                'sisa' => $balance->sisa,
            ];
        }));
    }

    /**
     * Get shikake balance (AJAX)
     */
    public function getShikakeBalance(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $masterShikakeId = $request->input('master_shikake_id');

        $balance = $this->defectService->getShikakeBalance($conveyorId, $masterShikakeId);

        if (!$balance) {
            return response()->json(['error' => 'Balance not found'], 404);
        }

        return response()->json($balance);
    }

    // =============================================
    // DEFECT HISTORY
    // =============================================

    /**
     * Display defect history page
     */
    public function history(Request $request)
    {
        $conveyors = MasterConveyor::whereNull('deleted_at')->orderBy('conveyor')->get();
        
        $shikakeTypes = [
            'BONDER' => 'Bonder',
            'DBL_CRIMP' => 'Dbl Crimp',
            'JOINT' => 'Joint',
            'SHIELD' => 'Shield',
            'TWIST' => 'Twist',
        ];

        return view('defect.history', [
            'conveyors' => $conveyors,
            'shikakeTypes' => $shikakeTypes,
        ]);
    }

    /**
     * Server-side datatable for defect history
     */
    public function historyDatatable(Request $request)
    {
        return $this->defectService->getHistoryDatatable(
            $request->input('type', 'circuit'),
            [
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'conveyor_id' => $request->input('conveyor_id'),
                'shift' => $request->input('shift'),
                'shikake_type' => $request->input('shikake_type'),
            ]
        );
    }

    /**
     * Get defect summary (AJAX)
     */
    public function getSummary(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $conveyorId = $request->input('conveyor_id');

        $circuitSummary = $this->defectService->getCircuitDefectSummary($dateFrom, $dateTo, $conveyorId);
        $shikakeSummary = $this->defectService->getShikakeDefectSummary($dateFrom, $dateTo, $conveyorId);

        return response()->json([
            'circuit' => $circuitSummary,
            'shikake' => $shikakeSummary,
            'total' => [
                'total_qty' => $circuitSummary['total_qty'] + $shikakeSummary['total_qty'],
                'total_count' => $circuitSummary['total_count'] + $shikakeSummary['total_count'],
            ],
        ]);
    }
}
