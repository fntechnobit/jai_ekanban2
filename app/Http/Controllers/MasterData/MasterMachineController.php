<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterMachineService;
use App\Http\Requests\MasterMachineRequest;
use App\Helpers\ResponseHelper;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use Illuminate\Http\Request;

class MasterMachineController extends Controller
{
    protected $masterMachineService;

    public function __construct(MasterMachineService $masterMachineService)
    {
        $this->masterMachineService = $masterMachineService;

        $this->middleware('check.menu:master_machine,can_read')->only(['index', 'datatable', 'show']);
        $this->middleware('check.menu:master_machine,can_create')->only(['create', 'store']);
        $this->middleware('check.menu:master_machine,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:master_machine,can_delete')->only(['destroy']);
    }

    public function index()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::with('area')->orderBy('conveyor')->get();
        return view('master_data.master_machine.index', compact('areas', 'conveyors'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $areaId = $request->get('area_id');
            $conveyorId = $request->get('conveyor_id');
            return $this->masterMachineService->getDatatable($areaId, $conveyorId);
        }
    }

    public function create()
    {
        return ResponseHelper::success();
    }

    public function store(MasterMachineRequest $request)
    {
        try {
            $machine = $this->masterMachineService->create($request->validated());
            return ResponseHelper::success($machine, 'Machine created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $machine = $this->masterMachineService->findById($id);
        $machine->conveyor_ids = $machine->conveyors->pluck('id')->toArray();
        return ResponseHelper::success($machine);
    }

    public function edit($id)
    {
        $machine = $this->masterMachineService->findById($id);
        $machine->conveyor_ids = $machine->conveyors->pluck('id')->toArray();
        return ResponseHelper::success($machine);
    }

    public function update(MasterMachineRequest $request, $id)
    {
        try {
            $machine = $this->masterMachineService->findById($id);
            $this->masterMachineService->update($machine, $request->validated());
            return ResponseHelper::success($machine, 'Machine updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $machine = $this->masterMachineService->findById($id);
            $this->masterMachineService->delete($machine);
            return ResponseHelper::success(null, 'Machine deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
