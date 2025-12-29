<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterShikakeService;
use App\Helpers\ResponseHelper;
use App\Helpers\ImageHelper;
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
        $this->middleware('check.menu:master_shikake,can_delete')->only(['destroy', 'removeByConveyor']);
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
        if (request()->ajax()) {
            $shikake = $this->masterShikakeService->findById($id);
            $shikake->load('assemblies', 'conveyor');
            
            // Convert to array to prevent auto-serialization
            $data = $shikake->toArray();
            
            // Format date for HTML date input
            if ($shikake->released_date) {
                $data['released_date'] = $shikake->released_date->format('Y-m-d');
            }
            
            return ResponseHelper::success($data);
        }
        
        $shikake = $this->masterShikakeService->findById($id);
        return view('master_data.master_shikake.view', compact('shikake'));
    }

    public function edit($id)
    {
        $shikake = $this->masterShikakeService->findById($id);
        $shikake->load('assemblies');
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_shikake.form', compact('shikake', 'areas', 'conveyors'));
    }

    public function update(Request $request, $id)
    {
        try {
            $shikake = $this->masterShikakeService->findById($id);
            
            $data = $request->all();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = ImageHelper::resizeAndSave($image, 'uploads/shikake', 200, 100);
                $data['image_path'] = 'uploads/shikake/' . $imageName;
                
                // Delete old image if exists
                if ($shikake->image_path && file_exists(public_path($shikake->image_path))) {
                    unlink(public_path($shikake->image_path));
                }
            }
            
            $this->masterShikakeService->update($shikake, $data);
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
            $request->validate([
                'conveyor_id' => 'required|exists:master_conveyor,id',
                'process' => 'required|string|in:BONDER,DBL CRIMP,JOINT,SHIELD,TWIST',
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
                'rows_start' => 'required|integer|min:1',
            ]);

            $file = $request->file('file');
            $conveyorId = $request->input('conveyor_id');
            $process = $request->input('process');
            $rowsStart = $request->input('rows_start', 2);

            // Import the data
            $result = $this->masterShikakeService->import($file->getRealPath(), $conveyorId, $process, $rowsStart);

            if ($result['success']) {
                $message = "Import completed successfully. {$result['success_count']} records imported";
                if ($result['failed_count'] > 0) {
                    $message .= ", {$result['failed_count']} records failed.";
                }
                
                return ResponseHelper::success([
                    'result' => $result,
                ], $message);
            } else {
                return ResponseHelper::error('Import failed', 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function downloadTemplate()
    {
        $filePath = public_path('docs/Template_Shikake.xlsx');
        
        if (!file_exists($filePath)) {
            return ResponseHelper::error('Template file not found', 404);
        }
        
        return response()->download($filePath, 'Template_Shikake.xlsx');
    }

    public function removeByConveyor(Request $request)
    {
        try {
            $request->validate([
                'conveyor_id' => 'required|exists:master_conveyor,id'
            ]);

            $deleted = $this->masterShikakeService->deleteByConveyor($request->conveyor_id);
            
            return ResponseHelper::success([
                'count' => $deleted
            ], "Successfully deleted {$deleted} Shikake record(s) for the selected conveyor");
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
