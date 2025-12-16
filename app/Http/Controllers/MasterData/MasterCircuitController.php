<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterCircuitService;
use App\Helpers\ResponseHelper;
use App\Helpers\ImageHelper;
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
        $this->middleware('check.menu:master_circuit,can_delete')->only(['destroy', 'removeByConveyor']);
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
        if (request()->ajax()) {
            $circuit = $this->masterCircuitService->findById($id);
            $circuit->load('assemblies', 'conveyor');
            
            // Convert to array to prevent auto-serialization
            $data = $circuit->toArray();
            
            // Format date for HTML date input
            if ($circuit->released_date) {
                $data['released_date'] = $circuit->released_date->format('Y-m-d');
            }
            
            return ResponseHelper::success($data);
        }
        
        $circuit = $this->masterCircuitService->findById($id);
        return view('master_data.master_circuit.view', compact('circuit'));
    }

    public function edit($id)
    {
        $circuit = $this->masterCircuitService->findById($id);
        $circuit->load('assemblies');
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        return view('master_data.master_circuit.form', compact('circuit', 'areas', 'conveyors'));
    }

    public function update(Request $request, $id)
    {
        try {
            $circuit = $this->masterCircuitService->findById($id);
            
            $data = $request->all();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = ImageHelper::resizeAndSave($image, 'uploads/circuit', 200, 100);
                $data['image_path'] = 'uploads/circuit/' . $imageName;
                
                // Delete old image if exists
                if ($circuit->image_path && file_exists(public_path($circuit->image_path))) {
                    unlink(public_path($circuit->image_path));
                }
            }
            
            $this->masterCircuitService->update($circuit, $data);
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
            $request->validate([
                'conveyor_id' => 'required|exists:master_conveyor,id',
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
                'rows_start' => 'required|integer|min:1',
            ]);

            $file = $request->file('file');
            $conveyorId = $request->input('conveyor_id');
            $rowsStart = $request->input('rows_start', 2);

            // Import the data
            $result = $this->masterCircuitService->import($file->getRealPath(), $conveyorId, $rowsStart);

            if ($result['success']) {
                $message = "Import completed successfully. {$result['success_count']} records imported";
                if ($result['failed_count'] > 0) {
                    $message .= ", {$result['failed_count']} records failed.";
                }
                
                return ResponseHelper::success([
                    'result' => $result,
                    'errors' => $result['errors'] ?? []
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
        $filePath = public_path('docs/Template_Cutting.xlsx');
        
        if (!file_exists($filePath)) {
            return ResponseHelper::error('Template file not found', 404);
        }
        
        return response()->download($filePath, 'Template_Cutting.xlsx');
    }

    public function removeByConveyor(Request $request)
    {
        try {
            $request->validate([
                'conveyor_id' => 'required|exists:master_conveyor,id'
            ]);

            $deleted = $this->masterCircuitService->deleteByConveyor($request->conveyor_id);
            
            return ResponseHelper::success([
                'count' => $deleted
            ], "Successfully deleted {$deleted} Circuit record(s) for the selected conveyor");
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
