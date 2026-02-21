<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterCarlineService;
use App\Http\Requests\MasterCarlineRequest;
use App\Helpers\ResponseHelper;
use App\Models\MasterArea;
use Illuminate\Http\Request;

class MasterCarlineController extends Controller
{
    protected $masterCarlineService;

    public function __construct(MasterCarlineService $masterCarlineService)
    {
        $this->masterCarlineService = $masterCarlineService;

        $this->middleware('check.menu:master_carline,can_read')->only(['index', 'datatable', 'show']);
        $this->middleware('check.menu:master_carline,can_create')->only(['create', 'store']);
        $this->middleware('check.menu:master_carline,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:master_carline,can_delete')->only(['destroy']);
    }

    public function index()
    {
        $areas = MasterArea::orderBy('area')->get();
        return view('master_data.master_carline.index', compact('areas'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            return $this->masterCarlineService->getDatatable();
        }
    }

    public function create()
    {
        return ResponseHelper::success();
    }

    public function store(MasterCarlineRequest $request)
    {
        try {
            $masterCarline = $this->masterCarlineService->create($request->validated());
            return ResponseHelper::success($masterCarline, 'Master carline created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $masterCarline = $this->masterCarlineService->findById($id);
        return ResponseHelper::success($masterCarline);
    }

    public function edit($id)
    {
        $masterCarline = $this->masterCarlineService->findById($id);
        return ResponseHelper::success($masterCarline);
    }

    public function update(MasterCarlineRequest $request, $id)
    {
        try {
            $masterCarline = $this->masterCarlineService->findById($id);
            $this->masterCarlineService->update($masterCarline, $request->validated());
            return ResponseHelper::success($masterCarline, 'Master carline updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $masterCarline = $this->masterCarlineService->findById($id);
            $this->masterCarlineService->delete($masterCarline);
            return ResponseHelper::success(null, 'Master carline deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
