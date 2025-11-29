<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\MasterAreaService;
use App\Http\Requests\MasterAreaRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class MasterAreaController extends Controller
{
    protected $masterAreaService;

    public function __construct(MasterAreaService $masterAreaService)
    {
        $this->masterAreaService = $masterAreaService;

        $this->middleware('check.menu:master_area,can_read')->only(['index', 'datatable', 'show']);
        $this->middleware('check.menu:master_area,can_create')->only(['create', 'store']);
        $this->middleware('check.menu:master_area,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:master_area,can_delete')->only(['destroy']);
    }

    public function index()
    {
        return view('system.master_area.index');
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            return $this->masterAreaService->getDatatable();
        }
    }

    public function create()
    {
        return ResponseHelper::success();
    }

    public function store(MasterAreaRequest $request)
    {
        try {
            $masterArea = $this->masterAreaService->create($request->validated());
            return ResponseHelper::success($masterArea, 'Master area created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $masterArea = $this->masterAreaService->findById($id);
        return ResponseHelper::success($masterArea);
    }

    public function edit($id)
    {
        $masterArea = $this->masterAreaService->findById($id);
        return ResponseHelper::success($masterArea);
    }

    public function update(MasterAreaRequest $request, $id)
    {
        try {
            $masterArea = $this->masterAreaService->findById($id);
            $this->masterAreaService->update($masterArea, $request->validated());
            return ResponseHelper::success($masterArea, 'Master area updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $masterArea = $this->masterAreaService->findById($id);
            $this->masterAreaService->delete($masterArea);
            return ResponseHelper::success(null, 'Master area deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
