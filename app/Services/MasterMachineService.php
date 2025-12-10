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
        return MasterMachine::with('conveyors')->select('master_machine.*');
    }

    public function getDatatable($conveyorId = null)
    {
        $query = MasterMachine::with(['conveyors', 'conveyors.area'])
            ->select('master_machine.*');

        // Filter by conveyor
        if ($conveyorId) {
            $query->whereHas('conveyors', function ($q) use ($conveyorId) {
                $q->where('master_conveyor.id', $conveyorId);
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('conveyor_names', function ($row) {
                return $row->conveyors->pluck('conveyor')->implode(', ') ?: '-';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = [];

                if ($currentUser && $currentUser->hasMenuPermission('master_machine', 'can_update')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' . $row->id . '" title="Edit"><i class="fas fa-edit"></i></button>';
                }

                if ($currentUser && $currentUser->hasMenuPermission('master_machine', 'can_delete')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                }

                return !empty($actions) ? implode(' ', $actions) : '-';
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
            return $machine->load('conveyors');
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
            return $machine->load('conveyors');
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
        return MasterMachine::with('conveyors')->findOrFail($id);
    }
}
