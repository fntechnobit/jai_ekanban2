<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterFamilyService;
use App\Http\Requests\MasterFamilyRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class MasterFamilyController extends Controller
{
    protected $masterFamilyService;

    public function __construct(MasterFamilyService $masterFamilyService)
    {
        $this->masterFamilyService = $masterFamilyService;

        $this->middleware('check.menu:master_family,can_read')->only(['index', 'datatable', 'show']);
        $this->middleware('check.menu:master_family,can_create')->only(['create', 'store']);
        $this->middleware('check.menu:master_family,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:master_family,can_delete')->only(['destroy']);
    }

    public function index()
    {
        return view('master_data.master_family.index');
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            return $this->masterFamilyService->getDatatable();
        }
    }

    public function create()
    {
        return ResponseHelper::success();
    }

    public function store(MasterFamilyRequest $request)
    {
        try {
            $masterFamily = $this->masterFamilyService->create($request->validated());
            return ResponseHelper::success($masterFamily, 'Master family created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $masterFamily = $this->masterFamilyService->findById($id);
        return ResponseHelper::success($masterFamily);
    }

    public function edit($id)
    {
        $masterFamily = $this->masterFamilyService->findById($id);
        return ResponseHelper::success($masterFamily);
    }

    public function update(MasterFamilyRequest $request, $id)
    {
        try {
            $masterFamily = $this->masterFamilyService->findById($id);
            $this->masterFamilyService->update($masterFamily, $request->validated());
            return ResponseHelper::success($masterFamily, 'Master family updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $masterFamily = $this->masterFamilyService->findById($id);
            $this->masterFamilyService->delete($masterFamily);
            return ResponseHelper::success(null, 'Master family deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
