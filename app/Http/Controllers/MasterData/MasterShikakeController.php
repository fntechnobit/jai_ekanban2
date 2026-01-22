<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\MasterShikakeService;
use App\Helpers\ResponseHelper;
use App\Helpers\ImageHelper;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Enums\ProcessType;
use App\Http\Requests\UpdateTwistShikakeRequest;
use App\Http\Requests\UpdateBonderShikakeRequest;
use App\Http\Requests\UpdateJointShikakeRequest;
use App\Http\Requests\UpdateShieldShikakeRequest;
use App\Http\Requests\UpdateDblCrimpShikakeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Validate request based on process type
     */
    private function validateByProcess(Request $request, $process)
    {
        try {
            $validator = null;
            $rules = [];
            $messages = [];

            switch ($process) {
                case 'TWIST':
                    $twistRequest = new UpdateTwistShikakeRequest();
                    $rules = $twistRequest->rules();
                    $messages = $twistRequest->messages();
                    break;
                case 'BONDER':
                    $bonderRequest = new UpdateBonderShikakeRequest();
                    $rules = $bonderRequest->rules();
                    $messages = $bonderRequest->messages();
                    break;
                case 'JOINT':
                    $jointRequest = new UpdateJointShikakeRequest();
                    $rules = $jointRequest->rules();
                    $messages = $jointRequest->messages();
                    break;
                case 'SHIELD':
                    $shieldRequest = new UpdateShieldShikakeRequest();
                    $rules = $shieldRequest->rules();
                    $messages = $shieldRequest->messages();
                    break;
                case 'DBL CRIMP':
                    $dblCrimpRequest = new UpdateDblCrimpShikakeRequest();
                    $rules = $dblCrimpRequest->rules();
                    $messages = $dblCrimpRequest->messages();
                    break;
                default:
                    return ResponseHelper::error('Invalid process type: ' . $process, 422);
            }

            // Create validator instance
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Update process-specific child table data
     */
    private function updateProcessData($shikake, $process, $processData)
    {
        switch ($process) {
            case 'TWIST':
                if ($shikake->twistData) {
                    $shikake->twistData->update($processData);
                } else {
                    $shikake->twistData()->create($processData);
                }
                break;
            case 'BONDER':
                if ($shikake->bonderData) {
                    $shikake->bonderData->update($processData);
                } else {
                    $shikake->bonderData()->create($processData);
                }
                break;
            case 'JOINT':
                if ($shikake->jointData) {
                    $shikake->jointData->update($processData);
                } else {
                    $shikake->jointData()->create($processData);
                }
                break;
            case 'SHIELD':
                if ($shikake->shieldData) {
                    $shikake->shieldData->update($processData);
                } else {
                    $shikake->shieldData()->create($processData);
                }
                break;
            case 'DBL CRIMP':
                if ($shikake->dblCrimpData) {
                    $shikake->dblCrimpData->update($processData);
                } else {
                    $shikake->dblCrimpData()->create($processData);
                }
                break;
        }
    }

    public function index()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        $processTypes = ProcessType::cases();
        return view('master_data.master_shikake.index', compact('areas', 'conveyors', 'processTypes'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $areaId = $request->get('area_id');
            $conveyorId = $request->get('conveyor_id');
            $process = $request->get('process');
            return $this->masterShikakeService->getDatatable($areaId, $conveyorId, $process);
        }
    }

    public function create()
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        $processTypes = ProcessType::cases();
        return view('master_data.master_shikake.form', compact('areas', 'conveyors', 'processTypes'));
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
            try {
                $shikake = $this->masterShikakeService->findById($id);
                $shikake->load('assemblies', 'conveyor');
                
                // Convert to array to prevent auto-serialization
                $data = $shikake->toArray();
                
                // Load process-specific child table data
                $data['process_data'] = [];
                switch ($shikake->process) {
                    case 'TWIST':
                        $childData = $shikake->twistData;
                        if ($childData) {
                            $data['process_data'] = $childData->toArray();
                        }
                        break;
                    case 'BONDER':
                        $childData = $shikake->bonderData;
                        if ($childData) {
                            $data['process_data'] = $childData->toArray();
                        }
                        break;
                    case 'JOINT':
                        $childData = $shikake->jointData;
                        if ($childData) {
                            $data['process_data'] = $childData->toArray();
                        }
                        break;
                    case 'SHIELD':
                        $childData = $shikake->shieldData;
                        if ($childData) {
                            $data['process_data'] = $childData->toArray();
                        }
                        break;
                    case 'DBL CRIMP':
                        $childData = $shikake->dblCrimpData;
                        if ($childData) {
                            $data['process_data'] = $childData->toArray();
                        }
                        break;
                }
                
                return ResponseHelper::success($data);
            } catch (\Exception $e) {
                Log::error('Error loading shikake details: ' . $e->getMessage());
                return ResponseHelper::error('Failed to load shikake details');
            }
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
        $processTypes = ProcessType::cases();
        return view('master_data.master_shikake.form', compact('shikake', 'areas', 'conveyors', 'processTypes'));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        
        try {
            $shikake = $this->masterShikakeService->findById($id);
            $process = $request->input('process', $shikake->process);
            
            // Validate based on process type using appropriate FormRequest
            $validationResult = $this->validateByProcess($request, $process);
            if ($validationResult !== true) {
                return $validationResult;
            }
            
            $data = $request->all();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = ImageHelper::resizeAndSave($image, 'uploads/shikake', 400, 233);
                $data['image_path'] = 'uploads/shikake/' . $imageName;
                
                // Delete old image if exists
                if ($shikake->image_path && file_exists(public_path($shikake->image_path))) {
                    unlink(public_path($shikake->image_path));
                }
            }
            
            // Update main shikake record
            $this->masterShikakeService->update($shikake, $data);
            
            // Update process-specific child table data
            $processData = $request->input('process_data', []);
            if (!empty($processData)) {
                $this->updateProcessData($shikake, $process, $processData);
            }
            
            DB::commit();
            
            // Reload the updated data
            $shikake->refresh();
            $shikake->load('assemblies', 'conveyor');
            
            return ResponseHelper::success($shikake, 'Shikake updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating shikake: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update shikake: ' . $e->getMessage());
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

    public function downloadTemplate(Request $request)
    {
        $process = $request->get('process');
        
        // Map process types to template files
        $templateMap = [
            'TWIST' => 'Template_Shikake_Twist.xlsx',
            'BONDER' => 'Template_Shikake_Bonder.xlsx',
            'JOINT' => 'Template_Shikake_Joint.xlsx',
            'SHIELD' => 'Template_Shikake_Shield.xlsx',
            'DBL CRIMP' => 'Template_Shikake_Dbl_Crimp.xlsx',
        ];

        // Default to base template if no process specified
        $templateFile = $templateMap[$process] ?? 'Template_Shikake.xlsx';
        $filePath = public_path('docs/' . $templateFile);
        
        if (!file_exists($filePath)) {
            return ResponseHelper::error('Template file not found for process: ' . ($process ?? 'default'), 404);
        }
        
        return response()->download($filePath, $templateFile);
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
