<?php

namespace App\Http\Controllers;

use App\Models\MasterConveyor;
use App\Models\MasterShikake;
use App\Models\KanbanBalance;
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

    /**
     * Display cutting defect form
     */
    public function cuttingIndex()
    {
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        
        return view('defect.cutting', [
            'conveyors' => $conveyors,
        ]);
    }

    /**
     * Store cutting defect
     */
    public function cuttingStore(Request $request)
    {
        $validated = $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'cct_no' => 'required|string',
            'cct_code' => 'required|string',
            'defect_date' => 'required|date|before_or_equal:today',
            'shift' => 'required|integer|in:1,2,3',
            'qty_defect' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->defectService->recordDefect(
            'circuit',
            $validated['conveyor_id'],
            [
                'cct_no' => $validated['cct_no'],
                'cct_code' => $validated['cct_code'],
            ],
            $validated['qty_defect'],
            [
                'date' => $validated['defect_date'],
                'shift' => $validated['shift'],
                'reason' => $validated['reason'],
            ]
        );

        if ($result['success']) {
            return redirect()->route('defect.cutting.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $result['message']);
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
            return [
                'cct_no' => $balance->cct_no,
                'cct_code' => $balance->cct_code,
                'sisa' => $balance->sisa,
                'display' => "{$balance->cct_no} - {$balance->cct_code} (Balance: {$balance->sisa})",
            ];
        }));
    }

    /**
     * Get circuit balance (AJAX)
     */
    public function getCircuitBalance(Request $request)
    {
        $conveyorId = $request->input('conveyor_id');
        $cctNo = $request->input('cct_no');
        $cctCode = $request->input('cct_code');

        $balance = $this->defectService->getCircuitBalance($conveyorId, $cctNo, $cctCode);

        if (!$balance) {
            return response()->json(['error' => 'Balance not found'], 404);
        }

        return response()->json($balance);
    }

    /**
     * Display shikake defect form
     */
    public function shikakeIndex()
    {
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        
        $shikakeTypes = [
            'BONDER' => 'Bonder',
            'DBL_CRIMP' => 'Dbl Crimp',
            'JOINT' => 'Joint',
            'SHIELD' => 'Shield',
            'TWIST' => 'Twist',
        ];

        return view('defect.shikake', [
            'conveyors' => $conveyors,
            'shikakeTypes' => $shikakeTypes,
        ]);
    }

    /**
     * Store shikake defect
     */
    public function shikakeStore(Request $request)
    {
        $validated = $request->validate([
            'conveyor_id' => 'required|exists:master_conveyor,id',
            'master_shikake_id' => 'required|exists:master_shikake,id',
            'shikake_type' => 'required|string|in:BONDER,DBL_CRIMP,JOINT,SHIELD,TWIST',
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
                'reason' => $validated['reason'],
            ]
        );

        if ($result['success']) {
            return redirect()->route('defect.shikake.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $result['message']);
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
            $shikake = $balance->shikake;
            $code = $shikake ? ($shikake->machine ?? "SHK-{$shikake->id}") : "Unknown";
            $process = $shikake ? $shikake->process : 'Unknown';
            
            return [
                'master_shikake_id' => $balance->master_shikake_id,
                'code' => $code,
                'process' => $process,
                'sisa' => $balance->sisa,
                'display' => "{$code} - {$process} (Balance: {$balance->sisa})",
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

    /**
     * Display defect history
     */
    public function history(Request $request)
    {
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'conveyor_id' => $request->input('conveyor_id'),
            'type' => $request->input('type'),
            'shift' => $request->input('shift'),
        ];

        $history = $this->defectService->getDefectHistory($filters, 20);

        return view('defect.history', [
            'conveyors' => $conveyors,
            'history' => $history,
            'filters' => $filters,
        ]);
    }

    /**
     * Get defect summary (AJAX)
     */
    public function getSummary(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $conveyorId = $request->input('conveyor_id');

        $summary = $this->defectService->getDefectSummary($dateFrom, $dateTo, $conveyorId);

        return response()->json($summary);
    }
}
