<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterCircuitService;
use App\Helpers\ResponseHelper;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use Illuminate\Http\Request;

class MasterCircuitController extends Controller
{
    protected $masterCircuitService;

    public function __construct(MasterCircuitService $masterCircuitService)
    {
        $this->masterCircuitService = $masterCircuitService;

        $this->middleware('check.menu:master_circuit,can_read')->only(['index', 'datatable', 'show']);
        $this->middleware('check.menu:master_circuit,can_create')->only(['create', 'store', 'importForm', 'import']);
        $this->middleware('check.menu:master_circuit,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:master_circuit,can_delete')->only(['destroy']);
    }

    public function index()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_circuit.index', compact('areas', 'conveyors'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $areaId = $request->get('area_id');
            $conveyorId = $request->get('conveyor_id');
            return $this->masterCircuitService->getDatatable($areaId, $conveyorId);
        }
    }

    public function create()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_circuit.form', compact('areas', 'conveyors'));
    }

    public function store(Request $request)
    {
        try {
            $circuit = $this->masterCircuitService->create($request->all());
            return ResponseHelper::success($circuit, 'Circuit created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $circuit = $this->masterCircuitService->findById($id);
        return view('master_data.master_circuit.view', compact('circuit'));
    }

    public function edit($id)
    {
        $circuit = $this->masterCircuitService->findById($id);
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_circuit.form', compact('circuit', 'areas', 'conveyors'));
    }

    public function update(Request $request, $id)
    {
        try {
            $circuit = $this->masterCircuitService->findById($id);
            $this->masterCircuitService->update($circuit, $request->all());
            return ResponseHelper::success($circuit, 'Circuit updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $circuit = $this->masterCircuitService->findById($id);
            $this->masterCircuitService->delete($circuit);
            return ResponseHelper::success(null, 'Circuit deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function importForm()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_circuit.import', compact('areas', 'conveyors'));
    }

    public function import(Request $request)
    {
        try {
            // Import logic will be implemented later
            return ResponseHelper::success(null, 'Import process will be implemented');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        // Template download logic will be implemented later
        return ResponseHelper::success(null, 'Template download will be implemented');
    }
}
