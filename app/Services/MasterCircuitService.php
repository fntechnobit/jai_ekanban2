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
            ->addColumn('carline', function ($row) {
                return $row->carline ?? '-';
            })
            ->addColumn('conveyor_name', function ($row) {
                return $row->getRelation('conveyor') ? $row->getRelation('conveyor')->conveyor : ($row->conveyor ?? '-');
            })
            ->filterColumn('carline', function($query, $keyword) {
                $query->where('master_circuit.carline', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                // View button
                if ($currentUser && $currentUser->hasMenuPermission('master_circuit', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-info btn-sm btn-view" data-id="' . $row->id . '" title="View"><i class="ti ti-eye"></i></button>';
                    $hasActions = true;
                }

                // Delete button
                if ($currentUser && $currentUser->hasMenuPermission('master_circuit', 'can_delete')) {
                    $actions .= '<button type="button" class="btn btn-soft-danger btn-sm btn-delete" data-id="' . $row->id . '" data-name="' . htmlspecialchars($row->machine_sequence ?? '-', ENT_QUOTES) . '" data-barcode="' . htmlspecialchars($row->barcode_kanban ?? '-', ENT_QUOTES) . '" title="Delete"><i class="ti ti-trash"></i></button>';
                    $hasActions = true;
                }

                $actions .= '</div>';
                return $hasActions ? $actions : '-';
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

    public function import($filePath, $conveyorId, $startRow = 2)
    {
        $importer = new \App\Imports\MasterCircuitImport($conveyorId);
        return $importer->import($filePath, $startRow);
    }

    public function deleteByConveyor($conveyorId)
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id();
            
            // Update deleted_by before soft deleting
            MasterCircuit::where('conveyor_id', $conveyorId)
                ->update(['deleted_by' => $userId]);
            
            // Soft delete all records for the conveyor
            $deleted = MasterCircuit::where('conveyor_id', $conveyorId)->delete();
            
            DB::commit();
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
