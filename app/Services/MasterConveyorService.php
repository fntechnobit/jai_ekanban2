<?php

namespace App\Services;

use App\Models\MasterConveyor;
use App\Models\MasterFamilyConveyor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MasterConveyorService
{
    public function getAll()
    {
        return MasterConveyor::with(['area', 'families'])->select('master_conveyor.*');
    }

    public function getDatatable($areaId = null, $familyId = null)
    {
        $query = MasterConveyor::with(['area', 'families'])
            ->select('master_conveyor.*');

        // Filter by area
        if ($areaId) {
            $query->where('master_area_id', $areaId);
        }

        // Filter by family
        if ($familyId) {
            $query->whereHas('families', function ($q) use ($familyId) {
                $q->where('master_family.id', $familyId);
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('area_name', function ($row) {
                return $row->area ? $row->area->area : '-';
            })
            ->addColumn('family_names', function ($row) {
                return $row->families->pluck('family')->implode(', ') ?: '-';
            })
            ->addColumn('shift_label', function ($row) {
                return $row->shift_qty . '/' . $row->shift_start;
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = [];

                if ($currentUser && $currentUser->hasMenuPermission('master_conveyor', 'can_update')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' . $row->id . '" title="Edit"><i class="fas fa-edit"></i></button>';
                }

                if ($currentUser && $currentUser->hasMenuPermission('master_conveyor', 'can_delete')) {
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
            $data['shift_start'] = 1; // Always set to 1
            
            $conveyor = MasterConveyor::create($data);

            // Attach families
            if (!empty($data['family_ids'])) {
                foreach ($data['family_ids'] as $familyId) {
                    MasterFamilyConveyor::create([
                        'conveyor_id' => $conveyor->id,
                        'family_id' => $familyId,
                    ]);
                }
            }

            DB::commit();
            return $conveyor->load('families');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(MasterConveyor $conveyor, array $data)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::id();
            $conveyor->update($data);

            // Sync families
            if (isset($data['family_ids'])) {
                // Remove existing
                MasterFamilyConveyor::where('conveyor_id', $conveyor->id)->delete();
                
                // Add new
                foreach ($data['family_ids'] as $familyId) {
                    MasterFamilyConveyor::create([
                        'conveyor_id' => $conveyor->id,
                        'family_id' => $familyId,
                    ]);
                }
            }

            DB::commit();
            return $conveyor->load('families');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(MasterConveyor $conveyor)
    {
        $conveyor->deleted_by = Auth::id();
        $conveyor->save();
        $conveyor->delete();
        return true;
    }

    public function findById($id)
    {
        return MasterConveyor::with('families')->findOrFail($id);
    }
}
