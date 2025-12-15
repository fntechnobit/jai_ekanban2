<?php

namespace App\Services;

use App\Models\MasterCircuit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MasterCircuitService
{
    public function getAll()
    {
        return MasterCircuit::with(['conveyor'])->select('master_circuit.*');
    }

    public function getDatatable($areaId = null, $conveyorId = null)
    {
        $query = MasterCircuit::with(['conveyor'])
            ->select('master_circuit.*');

        // Filter by area through conveyor relationship
        if ($areaId) {
            $query->whereHas('conveyor', function ($q) use ($areaId) {
                $q->where('master_area_id', $areaId);
            });
        }

        // Filter by conveyor
        if ($conveyorId) {
            $query->where('conveyor_id', $conveyorId);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('conveyor_name', function ($row) {
                return $row->conveyor ? $row->conveyor->conveyor : $row->conveyor ?? '-';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = [];

                // View button
                if ($currentUser && $currentUser->hasMenuPermission('master_circuit', 'can_read')) {
                    $actions[] = '<a href="' . route('master-data.master-circuit.show', $row->id) . '" class="btn btn-info btn-sm" title="View">
                        <i class="fas fa-eye"></i> View
                    </a>';
                }

                // Edit button
                if ($currentUser && $currentUser->hasMenuPermission('master_circuit', 'can_update')) {
                    $actions[] = '<a href="' . route('master-data.master-circuit.edit', $row->id) . '" class="btn btn-warning btn-sm" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>';
                }

                // Delete button
                if ($currentUser && $currentUser->hasMenuPermission('master_circuit', 'can_delete')) {
                    $actions[] = '<button type="button" class="btn btn-danger btn-sm btn-delete" data-id="' . $row->id . '" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>';
                }

                return implode(' ', $actions);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function findById($id)
    {
        return MasterCircuit::with(['conveyor'])->findOrFail($id);
    }

    public function create($data)
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = Auth::id();
            $circuit = MasterCircuit::create($data);

            DB::commit();
            return $circuit;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update($circuit, $data)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::id();
            $circuit->update($data);

            DB::commit();
            return $circuit;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete($circuit)
    {
        DB::beginTransaction();
        try {
            $circuit->deleted_by = Auth::id();
            $circuit->save();
            $circuit->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
