<?php

namespace App\Services;

use App\Models\AssyScheduleShikake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EkanbanShikakeService
{
    /**
     * Get shikake data for DataTable
     */
    public function getShikakeDataForTable(Request $request)
    {
        // Require machine filter
        if (!$request->filled('machine')) {
            return [
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ];
        }

        $query = $this->getBaseShikakeQuery();

        // Apply filters
        $this->applyFilters($query, $request);

        // DataTable processing
        $totalRecords = $query->count();
        
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function($q) use ($searchValue) {
                $q->where('tas.assy', 'like', "%{$searchValue}%")
                  ->orWhere('cv.conveyor', 'like', "%{$searchValue}%")
                  ->orWhere('master_shikake.shikake_no', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        // Order
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = [
            'assy_schedule_id',
            'master_shikake.shikake_no',
            'master_shikake.machine',
            'master_conveyor.conveyor',
            'assy_schedule.schedule',
            'assy_schedule.shift',
            'assy_schedule.assy',
            'assy_schedule.qty'
        ];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        // Handle "Show All" case (length = -1)
        if ($length == -1) {
            $data = $query->get();
        } else {
            $data = $query->skip($start)->take($length)->get();
        }

        $result = [];
        foreach ($data as $index => $row) {
            // Create composite ID for tracking individual shikakes
            $compositeId = $row->assy_schedule_id . '-' . $row->shikake_id;
            
            $result[] = [
                'DT_RowIndex' => $start + $index + 1,
                'assy_schedule_id' => $row->assy_schedule_id,
                'shikake_id' => $row->shikake_id,
                'composite_id' => $compositeId,
                'shikake_no' => $row->shikake_no,
                'shikake_code' => $row->shikake_code,
                'machine' => $row->machine,
                'conveyor' => $row->conveyor,
                'date' => Carbon::parse($row->date)->format('d-m-Y'),
                'shift' => 'Shift ' . $row->shift,
                'cutoff' => $row->cutoff ? 'Cut Off ' . $row->cutoff : '-',
                'assy' => $row->assy,
                'qty' => $row->qty,
                'is_printed' => $row->is_printed,
                'last_printed_at' => $row->last_printed_at,
                'print_count' => $row->print_count ?? 0,
                'actions' => view('schedule.ekanban_shikake.actions', ['row' => $row])->render()
            ];
        }

        return [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $result
        ];
    }

    /**
     * Get shikakes for printing - using composite IDs
     */
    public function getShikakesForPrint(array $ids)
    {
        // Parse composite IDs (format: "assyScheduleId-shikakeId")
        $conditions = [];
        foreach ($ids as $id) {
            if (strpos($id, '-') !== false) {
                [$assyScheduleId, $shikakeId] = explode('-', $id);
                $conditions[] = ['assy_schedule_id' => $assyScheduleId, 'shikake_id' => $shikakeId];
            }
        }

        if (empty($conditions)) {
            return collect([]);
        }

        // Build query with OR conditions for each composite ID
        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_assy', 'assy_schedule.assy', '=', 'master_assy.assy')
            ->join('master_shikake_assy', 'master_assy.id', '=', 'master_shikake_assy.master_assy_id')
            ->join('master_shikake', 'master_shikake_assy.master_shikake_id', '=', 'master_shikake.id')
            ->where(function($query) use ($conditions) {
                foreach ($conditions as $condition) {
                    $query->orWhere(function($q) use ($condition) {
                        $q->where('assy_schedule.id', $condition['assy_schedule_id'])
                          ->where('master_shikake.id', $condition['shikake_id']);
                    });
                }
            })
            ->select([
                'assy_schedule.*',
                'assy_schedule.id as assy_schedule_id',
                'master_conveyor.conveyor',
                'master_shikake.id as shikake_id',
                'master_shikake.shikake_no',
                'master_shikake.shikake_no as shikake_code',
                'master_conveyor.pallet_qty',
                DB::raw('CEIL(assy_schedule.qty / NULLIF(master_conveyor.pallet_qty, 0)) as pallet_count')
            ])
            ->get();
    }

    /**
     * Get base shikake query - Returns one row per shikake (no grouping)
     */
    private function getBaseShikakeQuery()
    {
        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_assy', 'assy_schedule.assy', '=', 'master_assy.assy')
            ->join('master_shikake_assy', 'master_assy.id', '=', 'master_shikake_assy.master_assy_id')
            ->join('master_shikake', 'master_shikake_assy.master_shikake_id', '=', 'master_shikake.id')
            ->leftJoin('assy_schedule_shikake', function($join) {
                $join->on('assy_schedule.id', '=', 'assy_schedule_shikake.assy_schedule_id')
                     ->on('master_shikake.id', '=', 'assy_schedule_shikake.shikake_id');
            })
            ->select([
                'assy_schedule.id as assy_schedule_id',
                'master_shikake.id as shikake_id',
                'master_shikake.shikake_no',
                'master_shikake.shikake_no as shikake_code',
                'master_shikake.machine',
                'master_conveyor.conveyor',
                'assy_schedule.schedule as date',
                'assy_schedule.shift',
                'assy_schedule.cutoff',
                'assy_schedule.assy',
                'assy_schedule.qty',
                'master_conveyor.pallet_qty',
                'assy_schedule_shikake.is_printed',
                'assy_schedule_shikake.last_printed_at',
                'assy_schedule_shikake.print_count'
            ])
            ->orderBy('master_shikake.shikake_no', 'ASC')
            ->orderBy('assy_schedule.schedule', 'ASC')
            ->orderBy('assy_schedule.shift', 'ASC');
    }

    /**
     * Mark shikakes as printed
     */
    public function markAsPrinted(array $compositeIds, $userId)
    {
        foreach ($compositeIds as $compositeId) {
            if (strpos($compositeId, '-') !== false) {
                [$assyScheduleId, $shikakeId] = explode('-', $compositeId);
                
                AssyScheduleShikake::updateOrCreate(
                    [
                        'assy_schedule_id' => $assyScheduleId,
                        'shikake_id' => $shikakeId
                    ],
                    [
                        'is_printed' => true,
                        'last_printed_at' => now(),
                        'last_printed_by' => $userId,
                        'print_count' => DB::raw('print_count + 1')
                    ]
                );
            }
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        $query->where('master_shikake.machine', $request->machine);

        if ($request->filled('area_id')) {
            $query->where('assy_schedule.area_id', $request->area_id);
        }

        if ($request->filled('cutoff')) {
            $query->where('assy_schedule.cutoff', $request->cutoff);
        }

        if ($request->filled('date')) {
            $query->whereDate('assy_schedule.schedule', $request->date);
        }

        if ($request->filled('shift')) {
            $query->where('assy_schedule.shift', $request->shift);
        }

        // Print status filter
        if ($request->filled('print_status')) {
            switch ($request->print_status) {
                case 'printed':
                    $query->where('assy_schedule_shikake.is_printed', 1);
                    break;
                case 'not_printed':
                    $query->where(function($q) {
                        $q->whereNull('assy_schedule_shikake.is_printed')
                          ->orWhere('assy_schedule_shikake.is_printed', 0);
                    });
                    break;
                case 'all':
                default:
                    // No filter
                    break;
            }
        }
    }
}