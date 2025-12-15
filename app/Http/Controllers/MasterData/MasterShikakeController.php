<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterShikakeService;
use App\Helpers\ResponseHelper;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use Illuminate\Http\Request;

class MasterShikakeController extends Controller
{
    protected $masterShikakeService;

    public function __construct(MasterShikakeService $masterShikakeService)
    {
        $this->masterShikakeService = $masterShikakeService;

        $this->middleware('check.menu:master_shikake,can_read')->only(['index', 'datatable', 'show']);
        $this->middleware('check.menu:master_shikake,can_create')->only(['create', 'store', 'importForm', 'import']);
        $this->middleware('check.menu:master_shikake,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:master_shikake,can_delete')->only(['destroy']);
    }

    public function index()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_shikake.index', compact('areas', 'conveyors'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $areaId = $request->get('area_id');
            $conveyorId = $request->get('conveyor_id');
            return $this->masterShikakeService->getDatatable($areaId, $conveyorId);
        }
    }

    public function create()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_shikake.form', compact('areas', 'conveyors'));
    }

    public function store(Request $request)
    {
        try {
            $shikake = $this->masterShikakeService->create($request->all());
            return ResponseHelper::success($shikake, 'Shikake created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $shikake = $this->masterShikakeService->findById($id);
        return view('master_data.master_shikake.view', compact('shikake'));
    }

    public function edit($id)
    {
        $shikake = $this->masterShikakeService->findById($id);
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_shikake.form', compact('shikake', 'areas', 'conveyors'));
    }

    public function update(Request $request, $id)
    {
        try {
            $shikake = $this->masterShikakeService->findById($id);
            $this->masterShikakeService->update($shikake, $request->all());
            return ResponseHelper::success($shikake, 'Shikake updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $shikake = $this->masterShikakeService->findById($id);
            $this->masterShikakeService->delete($shikake);
            return ResponseHelper::success(null, 'Shikake deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function importForm()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_shikake.import', compact('areas', 'conveyors'));
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
