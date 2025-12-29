<?php

namespace App\Services;

use App\Models\MasterShikake;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MasterShikakeService
{
    public function getAll()
    {
        return MasterShikake::with(['conveyor'])->select('master_shikake.*');
    }

    public function getDatatable($areaId = null, $conveyorId = null)
    {
        $query = MasterShikake::with(['conveyor'])
            ->select('master_shikake.*');

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
                return $row->getRelation('conveyor') ? $row->getRelation('conveyor')->conveyor : ($row->conveyor ?? '-');
            })
            ->addColumn('cct_code', function ($row) {
                // Combining cct fields for CCT Code display
                $cct_codes = array_filter([
                    $row->cct_a,
                    $row->cct_b,
                    $row->cct_c,
                    $row->cct_4,
                    $row->cct_5,
                    $row->cct_6,
                    $row->cct_7,
                ]);
                return !empty($cct_codes) ? implode(', ', $cct_codes) : '-';
            })
            ->addColumn('process', function ($row) {
                return $row->process ?? '-';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = [];

                // View/Edit button
                if ($currentUser && $currentUser->hasMenuPermission('master_shikake', 'can_update')) {
                    $actions[] = '<button type="button" class="btn btn-info btn-sm btn-view" data-id="' . $row->id . '" title="View">
                        <i class="fas fa-eye"></i> View
                    </button>';
                }

                // Delete button
                if ($currentUser && $currentUser->hasMenuPermission('master_shikake', 'can_delete')) {
                    $actions[] = '<button type="button" class="btn btn-danger btn-sm btn-delete" 
                        data-id="' . $row->id . '" 
                        data-name="' . htmlspecialchars($row->shikake_no ?? '-', ENT_QUOTES) . '" 
                        data-barcode="' . htmlspecialchars($row->barcode_kanban ?? '-', ENT_QUOTES) . '" 
                        title="Delete">
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
        return MasterShikake::with(['conveyor'])->findOrFail($id);
    }

    public function create($data)
    {
        DB::beginTransaction();
        try {
            $data['created_by'] = Auth::id();
            $shikake = MasterShikake::create($data);

            DB::commit();
            return $shikake;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update($shikake, $data)
    {
        DB::beginTransaction();
        try {
            $data['updated_by'] = Auth::id();
            $shikake->update($data);

            DB::commit();
            return $shikake;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete($shikake)
    {
        DB::beginTransaction();
        try {
            $shikake->deleted_by = Auth::id();
            $shikake->save();
            $shikake->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function import($filePath, $conveyorId, $process = null, $startRow = 2)
    {
        $importer = new \App\Imports\MasterShikakeImport($conveyorId, $process);
        return $importer->import($filePath, $startRow);
    }

    public function deleteByConveyor($conveyorId)
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id();
            
            // Update deleted_by before soft deleting
            MasterShikake::where('conveyor_id', $conveyorId)
                ->update(['deleted_by' => $userId]);
            
            // Soft delete all records for the conveyor
            $deleted = MasterShikake::where('conveyor_id', $conveyorId)->delete();
            
            DB::commit();
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
