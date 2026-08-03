<?php

namespace App\Http\Controllers;

use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Services\AdditionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdditionController extends Controller
{
    protected AdditionService $additionService;

    public function __construct(AdditionService $additionService)
    {
        $this->additionService = $additionService;
    }

    // =============================================
    // ADD CUTTING
    // =============================================

    /**
     * Display cutting addition page with datatable.
     */
    public function cuttingIndex()
    {
        $areas     = MasterArea::whereNull('deleted_at')->orderBy('area')->get();
        $conveyors = MasterConveyor::whereNull('deleted_at')->orderBy('conveyor')->get();

        return view('addition.cutting', [
            'areas'     => $areas,
            'conveyors' => $conveyors,
        ]);
    }

    /**
     * Server-side datatable for cutting circuits with balance.
     */
    public function cuttingDatatable(Request $request)
    {
        return $this->additionService->getCuttingDatatable(
            $request->input('conveyor_id'),
            $request->input('type'),
            $request->input('area_id')
        );
    }

    /**
     * Store cutting addition (AJAX).
     */
    public function cuttingStore(Request $request)
    {
        $validated = $request->validate([
            'conveyor_id'      => 'required|exists:master_conveyor,id',
            'master_circuit_id' => 'required|exists:master_circuit,id',
            'addition_date'    => 'required|date|before_or_equal:today',
            'shift'            => 'required|integer|in:1,2,3',
            'qty_addition'     => 'required|integer|min:1',
            'reason'           => 'nullable|string|max:500',
        ]);

        $result = $this->additionService->recordAddition(
            'circuit',
            $validated['conveyor_id'],
            [
                'master_circuit_id' => $validated['master_circuit_id'],
            ],
            $validated['qty_addition'],
            [
                'date'   => $validated['addition_date'],
                'shift'  => $validated['shift'],
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Parse an uploaded STO Circuit Scan History file (jai_sto_wip export) and
     * return a matched-vs-not-found preview, without writing to the database.
     */
    public function cuttingImportStoPreview(Request $request)
    {
        $validated = $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'file'        => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $result = $this->additionService->previewCuttingSto(
                $request->file('file')->getRealPath(),
                (int) $validated['conveyor_id']
            );

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('AdditionController: cuttingImportStoPreview failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Apply one chunk of previously previewed STO rows as balance additions
     * (AJAX). The frontend calls this repeatedly, one chunk at a time, to
     * drive an incremental progress bar.
     */
    public function cuttingImportStoCommit(Request $request)
    {
        $validated = $request->validate([
            'conveyor_id'                => 'required|exists:master_conveyor,id',
            'addition_date'              => 'required|date|before_or_equal:today',
            'shift'                      => 'required|integer|in:1,2,3',
            'items'                      => 'required|array|min:1|max:200',
            'items.*.master_circuit_id'  => 'required|integer|exists:master_circuit,id',
            'items.*.cct_code'           => 'required|string',
            'items.*.qty'                => 'required|integer|min:1',
        ]);

        try {
            $result = $this->additionService->commitCuttingAdditions(
                (int) $validated['conveyor_id'],
                $validated['addition_date'],
                (int) $validated['shift'],
                $validated['items']
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('AdditionController: cuttingImportStoCommit failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get circuits for a conveyor (AJAX).
     */
    public function getCircuits(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        if (!$conveyorId) {
            return response()->json([]);
        }

        $circuits = $this->additionService->getCircuitsWithBalance($conveyorId);

        return response()->json($circuits->map(function ($balance) {
            $circuit = $balance->masterCircuit;
            return [
                'master_circuit_id' => $balance->master_circuit_id,
                'cct_no'            => $circuit ? $circuit->cct_no : 'Unknown',
                'cct_code'          => $circuit ? $circuit->cct_code : 'Unknown',
                'sisa'              => $balance->sisa,
            ];
        }));
    }

    /**
     * Get circuit balance (AJAX).
     */
    public function getCircuitBalance(Request $request)
    {
        $conveyorId      = $request->input('conveyor_id');
        $masterCircuitId = $request->input('master_circuit_id');

        $balance = $this->additionService->getCircuitBalance($conveyorId, $masterCircuitId);

        if (!$balance) {
            return response()->json(['error' => 'Balance not found'], 404);
        }

        return response()->json($balance);
    }

    // =============================================
    // ADD SHIKAKE
    // =============================================

    /**
     * Display shikake addition page with datatable.
     */
    public function shikakeIndex()
    {
        $areas     = MasterArea::whereNull('deleted_at')->orderBy('area')->get();
        $conveyors = MasterConveyor::whereNull('deleted_at')->orderBy('conveyor')->get();

        $shikakeTypes = [
            'BONDER'    => 'Bonder',
            'DBL CRIMP' => 'Dbl Crimp',
            'JOINT'     => 'Joint',
            'SHIELD'    => 'Shield',
            'TWIST'     => 'Twist',
        ];

        return view('addition.shikake', [
            'areas'        => $areas,
            'conveyors'    => $conveyors,
            'shikakeTypes' => $shikakeTypes,
        ]);
    }

    /**
     * Server-side datatable for shikake with balance.
     */
    public function shikakeDatatable(Request $request)
    {
        return $this->additionService->getShikakeDatatable(
            $request->input('conveyor_id'),
            $request->input('process_type'),
            $request->input('area_id')
        );
    }

    /**
     * Store shikake addition (AJAX).
     */
    public function shikakeStore(Request $request)
    {
        $validated = $request->validate([
            'conveyor_id'       => 'required|exists:master_conveyor,id',
            'master_shikake_id' => 'required|exists:master_shikake,id',
            'shikake_type'      => 'required|string',
            'addition_date'     => 'required|date|before_or_equal:today',
            'shift'             => 'required|integer|in:1,2,3',
            'qty_addition'      => 'required|integer|min:1',
            'reason'            => 'nullable|string|max:500',
        ]);

        $result = $this->additionService->recordAddition(
            'shikake',
            $validated['conveyor_id'],
            [
                'master_shikake_id' => $validated['master_shikake_id'],
                'shikake_type'      => $validated['shikake_type'],
            ],
            $validated['qty_addition'],
            [
                'date'   => $validated['addition_date'],
                'shift'  => $validated['shift'],
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get shikakes for a conveyor and type (AJAX).
     */
    public function getShikakes(Request $request)
    {
        $conveyorId  = $request->input('conveyor_id');
        $shikakeType = $request->input('shikake_type');

        if (!$conveyorId) {
            return response()->json([]);
        }

        $shikakes = $this->additionService->getShikakesWithBalance($conveyorId, $shikakeType);

        return response()->json($shikakes->map(function ($balance) {
            $shikake = $balance->masterShikake;
            $code    = $shikake ? ($shikake->machine ?? "SHK-{$shikake->id}") : 'Unknown';
            $process = $shikake ? $shikake->process : 'Unknown';

            return [
                'master_shikake_id' => $balance->master_shikake_id,
                'code'              => $code,
                'process'           => $process,
                'sisa'              => $balance->sisa,
            ];
        }));
    }

    /**
     * Get shikake balance (AJAX).
     */
    public function getShikakeBalance(Request $request)
    {
        $conveyorId      = $request->input('conveyor_id');
        $masterShikakeId = $request->input('master_shikake_id');

        $balance = $this->additionService->getShikakeBalance($conveyorId, $masterShikakeId);

        if (!$balance) {
            return response()->json(['error' => 'Balance not found'], 404);
        }

        return response()->json($balance);
    }

    // =============================================
    // ADD HISTORY
    // =============================================

    /**
     * Display addition history page.
     */
    public function history(Request $request)
    {
        $conveyors = MasterConveyor::whereNull('deleted_at')->orderBy('conveyor')->get();

        $shikakeTypes = [
            'BONDER'    => 'Bonder',
            'DBL_CRIMP' => 'Dbl Crimp',
            'JOINT'     => 'Joint',
            'SHIELD'    => 'Shield',
            'TWIST'     => 'Twist',
        ];

        return view('addition.history', [
            'conveyors'    => $conveyors,
            'shikakeTypes' => $shikakeTypes,
        ]);
    }

    /**
     * Server-side datatable for addition history.
     */
    public function historyDatatable(Request $request)
    {
        return $this->additionService->getHistoryDatatable(
            $request->input('type', 'circuit'),
            [
                'date_from'    => $request->input('date_from'),
                'date_to'      => $request->input('date_to'),
                'conveyor_id'  => $request->input('conveyor_id'),
                'shift'        => $request->input('shift'),
                'shikake_type' => $request->input('shikake_type'),
            ]
        );
    }

    /**
     * Get addition summary (AJAX).
     */
    public function getSummary(Request $request)
    {
        $dateFrom  = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo    = $request->input('date_to', now()->toDateString());
        $conveyorId = $request->input('conveyor_id');

        $circuitSummary = $this->additionService->getCircuitAdditionSummary($dateFrom, $dateTo, $conveyorId);
        $shikakeSummary = $this->additionService->getShikakeAdditionSummary($dateFrom, $dateTo, $conveyorId);

        return response()->json([
            'circuit' => $circuitSummary,
            'shikake' => $shikakeSummary,
            'total'   => [
                'total_qty'   => $circuitSummary['total_qty'] + $shikakeSummary['total_qty'],
                'total_count' => $circuitSummary['total_count'] + $shikakeSummary['total_count'],
            ],
        ]);
    }
}
