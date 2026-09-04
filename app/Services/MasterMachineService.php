<?php

namespace App\Services;

use App\Models\MasterMachine;
use App\Models\MasterMachineConveyor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MasterMachineService
{
    public function getAll()
    {
        return MasterMachine::with(['area', 'conveyors'])->select('master_machine.*');
    }

    public function getDatatable($areaId = null, $conveyorId = null)
    {
        $query = MasterMachine::with(['area', 'conveyors', 'conveyors.area'])
            ->select('master_machine.*');

        // Filter by area
        if ($areaId) {
            $query->where('master_machine.master_area_id', $areaId);
        }

        // Filter by conveyor
        if ($conveyorId) {
            $query->whereHas('conveyors', function ($q) use ($conveyorId) {
                $q->where('master_conveyor.id', $conveyorId);
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('area_name', function ($row) {
                return $row->area->area ?? '-';
            })
            ->addColumn('conveyor_names', function ($row) {
                return $row->conveyors->pluck('conveyor')->implode(', ') ?: '-';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                if ($currentUser && $currentUser->hasMenuPermission('master_machine', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-primary btn-sm btn-edit" data-id="' . $row->id . '" title="Edit"><i class="ti ti-pencil"></i></button>';
                    $hasActions = true;
                }

                if ($currentUser && $currentUser->hasMenuPermission('master_machine', 'can_delete')) {
                    $actions .= '<button type="button" class="btn btn-soft-danger btn-sm btn-delete" data-id="' . $row->id . '" title="Delete"><i class="ti ti-trash"></i></button>';
                    $hasActions = true;
                }

                $actions .= '</div>';
                return $hasActions ? $actions : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = Auth::id();
            
            $machine = MasterMachine::create($data);

            // Attach conveyors
            if (!empty($data['conveyor_ids'])) {
                foreach ($data['conveyor_ids'] as $conveyorId) {
                    MasterMachineConveyor::create([
                        'machine_id' => $machine->id,
                        'conveyor_id' => $conveyorId,
                    ]);
                }
            }

            DB::commit();
            return $machine->load(['area', 'conveyors']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(MasterMachine $machine, array $data)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::id();
            $machine->update($data);

            // Sync conveyors
            if (isset($data['conveyor_ids'])) {
                // Remove existing
                MasterMachineConveyor::where('machine_id', $machine->id)->delete();
                
                // Add new
                foreach ($data['conveyor_ids'] as $conveyorId) {
                    MasterMachineConveyor::create([
                        'machine_id' => $machine->id,
                        'conveyor_id' => $conveyorId,
                    ]);
                }
            }

            DB::commit();
            return $machine->load(['area', 'conveyors']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(MasterMachine $machine)
    {
        $machine->deleted_by = Auth::id();
        $machine->save();
        $machine->delete();
        return true;
    }

    public function findById($id)
    {
        return MasterMachine::with(['area', 'conveyors'])->findOrFail($id);
    }
}
