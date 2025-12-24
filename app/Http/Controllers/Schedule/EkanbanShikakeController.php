<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\MasterArea;
use App\Models\MasterConveyor;
use App\Models\MasterMachine;
use App\Models\AssySchedule;
use App\Services\EkanbanShikakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EkanbanShikakeController extends Controller
{
    protected $ekanbanShikakeService;

    public function __construct(EkanbanShikakeService $ekanbanShikakeService)
    {
        $this->ekanbanShikakeService = $ekanbanShikakeService;
    }
    /**
     * Show print per machine page
     */
    public function printMachine(Request $request)
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        $machines = MasterMachine::orderBy('machine')->get();

        if ($request->ajax()) {
            $data = $this->ekanbanShikakeService->getShikakeDataForTable($request);
            return response()->json($data);
        }

        return view('schedule.ekanban_shikake.print_machine', compact('areas', 'conveyors', 'machines'));
    }

    /**
     * Show print preview page
     */
    public function printPreview(Request $request)
    {
        $areas = MasterArea::orderBy('area')->get();
        $conveyors = MasterConveyor::orderBy('conveyor')->get();
        $machines = MasterMachine::orderBy('machine')->get();

        if ($request->ajax()) {
            $data = $this->ekanbanShikakeService->getShikakeDataForTable($request);
            return response()->json($data);
        }

        return view('schedule.ekanban_shikake.print_preview', compact('areas', 'conveyors', 'machines'));
    }

    /**
     * Print individual shikake
     */
    public function print(Request $request)
    {
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
        
        $shikakes = $this->ekanbanShikakeService->getShikakesForPrint($ids);

        $html = view('schedule.ekanban_shikake.print_ticket', compact('shikakes'))->render();

        return response()->json([
            'ok' => true,
            'html' => $html
        ]);
    }

    /**
     * Get machines by conveyor for dynamic filtering
     */
    public function getMachinesByConveyor(Request $request)
    {
        $conveyorId = $request->conveyor_id;
        
        if (!$conveyorId) {
            return response()->json([]);
        }

        $machines = DB::table('master_shikake')
            ->join('master_conveyor', 'master_shikake.conveyor_id', '=', 'master_conveyor.id')
            ->where('master_conveyor.id', $conveyorId)
            ->whereNotNull('master_shikake.machine')
            ->where('master_shikake.machine', '!=', '')
            ->select('master_shikake.machine')
            ->distinct()
            ->orderBy('master_shikake.machine')
            ->pluck('machine');

        // Format for select dropdown
        $formattedMachines = $machines->map(function($machine) {
            return [
                'machine' => $machine,
                'name' => $machine
            ];
        });

        return response()->json($formattedMachines);
    }
}