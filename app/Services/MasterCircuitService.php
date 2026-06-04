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

    public function getDatatable($areaId = null, $conveyorId = null, $type = null)
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

        // Filter by type
        if ($type) {
            $query->where('type', $type);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('type_badge', function ($row) {
                $type = $row->type ?? 'CUTTING';
                if ($type === 'CUTTING_TWIST') {
                    return '<span class="badge bg-warning text-dark fw-semibold">TWS</span>';
                }
                return '<span class="badge bg-info text-white fw-semibold">CCT</span>';
            })
            ->addColumn('carline', function ($row) {
                return $row->carline ?? '-';
            })
            ->addColumn('conveyor_name', function ($row) {
                return $row->getRelation('conveyor') ? $row->getRelation('conveyor')->conveyor : ($row->conveyor ?? '-');
            })
            ->addColumn('shikake_code', function ($row) {
                return $row->shikake_code ?? '-';
            })
            ->filterColumn('carline', function($query, $keyword) {
                $query->where('master_circuit.carline', 'like', "%{$keyword}%");
            })
            ->filterColumn('shikake_code', function($query, $keyword) {
                $query->where('master_circuit.shikake_code', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                // View button (read-only)
                if ($currentUser && $currentUser->hasMenuPermission('master_circuit', 'can_read')) {
                    $actions .= '<button type="button" class="btn btn-soft-info btn-sm btn-view" data-id="' . $row->id . '" title="View"><i class="ti ti-eye"></i></button>';
                    $hasActions = true;
                }

                // Edit button
                if ($currentUser && $currentUser->hasMenuPermission('master_circuit', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-primary btn-sm btn-edit" data-id="' . $row->id . '" title="Edit"><i class="ti ti-pencil"></i></button>';
                    $hasActions = true;
                }

                // Delete button
                if ($currentUser && $currentUser->hasMenuPermission('master_circuit', 'can_delete')) {
                    $conveyorName = $row->getRelation('conveyor') ? $row->getRelation('conveyor')->conveyor : '-';
                    $actions .= '<button type="button" class="btn btn-soft-danger btn-sm btn-delete" data-id="' . $row->id . '" data-type="' . htmlspecialchars($row->type ?? 'CUTTING', ENT_QUOTES) . '" data-cct-no="' . htmlspecialchars($row->cct_no ?? '-', ENT_QUOTES) . '" data-conveyor="' . htmlspecialchars($conveyorName, ENT_QUOTES) . '" data-carline="' . htmlspecialchars($row->carline ?? '-', ENT_QUOTES) . '" title="Delete"><i class="ti ti-trash"></i></button>';
                    $hasActions = true;
                }

                $actions .= '</div>';
                return $hasActions ? $actions : '-';
            })
            ->rawColumns(['type_badge', 'action'])
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
            $conveyorId = $data['conveyor_id'] ?? null;
            $cctCode = $data['cct_code'] ?? null;
            $toStore = $data['to_store'] ?? null;

            // Enforce uniqueness on (conveyor_id, cct_code, to_store). The same
            // cct_code may repeat within a conveyor only when to_store differs.
            if ($conveyorId && $cctCode && $toStore !== null && $toStore !== '') {
                $existing = MasterCircuit::withTrashed()
                    ->where('conveyor_id', $conveyorId)
                    ->where('cct_code', $cctCode)
                    ->where('to_store', $toStore)
                    ->first();

                if ($existing) {
                    // A soft-deleted match is restored and updated instead of
                    // colliding with the unique constraint.
                    if ($existing->trashed()) {
                        $existing->restore();
                        $data['updated_by'] = Auth::id();
                        $existing->deleted_by = null;
                        $existing->update($data);

                        DB::commit();
                        return $existing;
                    }

                    throw new \Exception("Circuit with CCT Code '{$cctCode}' and To Store '{$toStore}' already exists on this conveyor.");
                }
            }

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
            $conveyorId = $data['conveyor_id'] ?? $circuit->conveyor_id;
            $cctCode = array_key_exists('cct_code', $data) ? $data['cct_code'] : $circuit->cct_code;
            $toStore = array_key_exists('to_store', $data) ? $data['to_store'] : $circuit->to_store;

            // Block updates that would duplicate another circuit's
            // (conveyor_id, cct_code, to_store) combination.
            if ($conveyorId && $cctCode && $toStore !== null && $toStore !== '') {
                $duplicate = MasterCircuit::where('conveyor_id', $conveyorId)
                    ->where('cct_code', $cctCode)
                    ->where('to_store', $toStore)
                    ->where('id', '!=', $circuit->id)
                    ->first();

                if ($duplicate) {
                    throw new \Exception("Circuit with CCT Code '{$cctCode}' and To Store '{$toStore}' already exists on this conveyor.");
                }
            }

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
