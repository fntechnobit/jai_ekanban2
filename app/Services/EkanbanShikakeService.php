<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EkanbanShikakeService
{
    /**
     * Get shikake data for DataTable
     */
    public function getShikakeDataForTable(Request $request)
    {
        // Require machine and conveyor filters
        if (!$request->filled('machine') || !$request->filled('conveyor_id')) {
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
        $columns = ['id', 'conveyor', 'machine', 'dates', 'shift', 'assy', 'listing', 'pallet', 'wd'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $data = $query->skip($start)->take($length)->get();

        $result = [];
        foreach ($data as $index => $row) {
            $result[] = [
                'DT_RowIndex' => $start + $index + 1,
                'conveyor' => $row->conveyor,
                'machine' => $row->machine,
                'dates' => Carbon::parse($row->dates)->format('d-m-Y'),
                'shift' => 'Shift ' . $row->shift,
                'assy' => $row->assy,
                'listing' => $row->listing,
                'pallet' => $row->pallet,
                'wd' => $row->wd,
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
     * Get shikakes for printing
     */
    public function getShikakesForPrint(array $ids)
    {
        return DB::table('assy_schedule')
            ->join('master_conveyor', 'assy_schedule.conveyor_id', '=', 'master_conveyor.id')
            ->join('master_assy', 'assy_schedule.assy', '=', 'master_assy.assy')
            ->join('master_shikake_assy', 'master_assy.id', '=', 'master_shikake_assy.master_assy_id')
            ->join('master_shikake', 'master_shikake_assy.master_shikake_id', '=', 'master_shikake.id')
            ->whereIn('assy_schedule.id', $ids)
            ->select([
                'assy_schedule.*',
                'master_conveyor.conveyor',
                'master_shikake.shikake_no as shikake_name',
                'master_shikake.shikake_no as shikake_code',
                'master_conveyor.pallet_qty',
                DB::raw('CEIL(assy_schedule.qty / NULLIF(master_conveyor.pallet_qty, 0)) as pallet_count')
            ])
            ->get();
    }

    /**
     * Get base shikake query - Fixed to show data
     */
    private function getBaseShikakeQuery()
    {
        return DB::table('assy_schedule as tas')
            ->join('master_conveyor as cv', 'cv.id', '=', 'tas.conveyor_id')
            ->join('master_assy', 'tas.assy', '=', 'master_assy.assy')
            ->join('master_shikake_assy', 'master_assy.id', '=', 'master_shikake_assy.master_assy_id')
            ->join('master_shikake', 'master_shikake_assy.master_shikake_id', '=', 'master_shikake.id')
            ->select([
                DB::raw('MIN(tas.id) as id'),
                'cv.conveyor',
                DB::raw('MAX(tas.schedule) as dates'),
                'tas.shift',
                'tas.assy',
                DB::raw('SUM(tas.qty) as listing'),
                DB::raw('MAX(cv.pallet_qty) as pallet_qty'),
                DB::raw('MAX(cv.pallet_qty) as pallet'),
                DB::raw('CEIL(SUM(tas.qty) / NULLIF(MAX(cv.pallet_qty), 0)) as wd'),
                'master_shikake.machine'
            ])
            ->groupBy('cv.conveyor', 'tas.shift', 'tas.assy', 'master_shikake.machine')
            ->orderBy('cv.conveyor', 'ASC')
            ->orderBy('tas.shift', 'ASC')
            ->orderBy('tas.assy', 'ASC');
    }

    /**
     * Apply filters to query - Simplified
     */
    private function applyFilters($query, Request $request)
    {
        $query->where('cv.id', $request->conveyor_id);
        $query->where('master_shikake.machine', $request->machine);

        if ($request->filled('area_id')) {
            $query->where('tas.area_id', $request->area_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('tas.schedule', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('tas.schedule', '<=', $request->end_date);
        }

        if ($request->filled('shift')) {
            $query->where('tas.shift', $request->shift);
        }
    }
}