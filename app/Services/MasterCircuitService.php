<?php

namespace App\Services;

use App\Models\MasterCircuit;
use App\Models\MasterConveyor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MasterCircuitService
{
    public function getAll()
    {
        return MasterCircuit::with(['conveyor'])->select('master_circuit.*');
    }

    public function getDatatable(array $filters = [])
    {
        // Area -> Family -> Conveyor -> Type -> Machine
        $query = $this->filteredQuery($filters)
            ->with(['conveyor.area'])
            ->select('master_circuit.*');

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
            ->addColumn('area_name', function ($row) {
                return $row->getRelation('conveyor')?->area?->area ?? '-';
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
            // An empty to_store is itself a value to match on (not a wildcard
            // that skips the check), otherwise the same cct_code with a blank
            // to_store could be saved twice as separate rows.
            if ($conveyorId && $cctCode) {
                $existing = MasterCircuit::withTrashed()
                    ->where('conveyor_id', $conveyorId)
                    ->where('cct_code', $cctCode)
                    ->where(function ($q) use ($toStore) {
                        if ($toStore !== null && $toStore !== '') {
                            $q->where('to_store', $toStore);
                        } else {
                            $q->whereNull('to_store')->orWhere('to_store', '');
                        }
                    })
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
            // (conveyor_id, cct_code, to_store) combination. An empty to_store
            // is itself a value to match on, same as in create().
            if ($conveyorId && $cctCode) {
                $duplicate = MasterCircuit::where('conveyor_id', $conveyorId)
                    ->where('cct_code', $cctCode)
                    ->where(function ($q) use ($toStore) {
                        if ($toStore !== null && $toStore !== '') {
                            $q->where('to_store', $toStore);
                        } else {
                            $q->whereNull('to_store')->orWhere('to_store', '');
                        }
                    })
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

    /**
     * Data query narrowed by the cascading filters (Area -> Family -> Conveyor -> Type -> Machine).
     */
    private function filteredQuery(array $filters)
    {
        return MasterCircuit::query()
            ->when($filters['area_id'] ?? null, fn ($q, $areaId) => $q->whereHas('conveyor', fn ($c) => $c->where('master_area_id', $areaId)))
            ->when($filters['family'] ?? null, fn ($q, $family) => $q->where('family', $family))
            ->when($filters['conveyor_id'] ?? null, fn ($q, $conveyorId) => $q->where('conveyor_id', $conveyorId))
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('type', $value))
            ->when($filters['machine'] ?? null, fn ($q, $machine) => $q->where('machine', $machine));
    }

    /**
     * Options for the cascading selects, taken from the data itself so every choice
     * matches existing rows. Each level is narrowed only by the levels above it.
     */
    public function getFilterOptions(array $filters)
    {
        $distinct = fn (array $levels, string $column) => $this->filteredQuery(Arr::only($filters, $levels))
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);

        $conveyorIds = $this->filteredQuery(Arr::only($filters, ['area_id', 'family']))->distinct()->pluck('conveyor_id');

        return [
            'families' => $distinct(['area_id'], 'family'),
            'conveyors' => MasterConveyor::whereIn('id', $conveyorIds)
                ->orderBy('conveyor')
                ->get(['id', 'conveyor'])
                ->map(fn ($c) => ['id' => $c->id, 'text' => $c->conveyor])
                ->values(),
            'machines' => $distinct(['area_id', 'family', 'conveyor_id', 'type'], 'machine'),
        ];
    }

    /**
     * Soft delete every row on one conveyor matching the given family / type / machine.
     */
    public function deleteByFilters(array $filters)
    {
        if (empty($filters['conveyor_id'])) {
            throw new \InvalidArgumentException('A conveyor is required to remove data');
        }

        DB::beginTransaction();
        try {
            $userId = Auth::id();

            $query = fn () => $this->filteredQuery(Arr::only($filters, ['conveyor_id', 'family', 'type', 'machine']));

            // Update deleted_by before soft deleting
            $query()->update(['deleted_by' => $userId]);

            $deleted = $query()->delete();

            DB::commit();
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
