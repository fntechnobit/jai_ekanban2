<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterConveyorService;
use App\Http\Requests\MasterConveyorRequest;
use App\Helpers\ResponseHelper;
use App\Models\MasterArea;
use App\Models\MasterFamily;
use Illuminate\Http\Request;

class MasterConveyorController extends Controller
{
    protected $masterConveyorService;

    public function __construct(MasterConveyorService $masterConveyorService)
    {
        $this->masterConveyorService = $masterConveyorService;

        $this->middleware('check.menu:master_conveyor,can_read')->only(['index', 'datatable', 'show']);
        $this->middleware('check.menu:master_conveyor,can_create')->only(['create', 'store']);
        $this->middleware('check.menu:master_conveyor,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:master_conveyor,can_delete')->only(['destroy']);
    }

    public function index()
    {
        $areas = MasterArea::orderBy('area')->get();
        $families = MasterFamily::orderBy('family')->get();
        return view('master_data.master_conveyor.index', compact('areas', 'families'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $areaId = $request->get('area_id');
            $familyId = $request->get('family_id');
            return $this->masterConveyorService->getDatatable($areaId, $familyId);
        }
    }

    public function create()
    {
        return ResponseHelper::success();
    }

    public function store(MasterConveyorRequest $request)
    {
        try {
            $conveyor = $this->masterConveyorService->create($request->validated());
            return ResponseHelper::success($conveyor, 'Conveyor created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $conveyor = $this->masterConveyorService->findById($id);
        $conveyor->family_ids = $conveyor->families->pluck('id')->toArray();
        return ResponseHelper::success($conveyor);
    }

    public function edit($id)
    {
        $conveyor = $this->masterConveyorService->findById($id);
        $conveyor->family_ids = $conveyor->families->pluck('id')->toArray();
        return ResponseHelper::success($conveyor);
    }

    public function update(MasterConveyorRequest $request, $id)
    {
        try {
            $conveyor = $this->masterConveyorService->findById($id);
            $this->masterConveyorService->update($conveyor, $request->validated());
            return ResponseHelper::success($conveyor, 'Conveyor updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $conveyor = $this->masterConveyorService->findById($id);
            $this->masterConveyorService->delete($conveyor);
            return ResponseHelper::success(null, 'Conveyor deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
