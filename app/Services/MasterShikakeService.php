<?php

namespace App\Services;

use App\Models\MasterShikake;
use App\Imports\MasterShikakeTwistImport;
use App\Imports\MasterShikakeBonderImport;
use App\Imports\MasterShikakeJointImport;
use App\Imports\MasterShikakeShieldImport;
use App\Imports\MasterShikakeDblCrimpImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MasterShikakeService
{
    public function getAll()
    {
        return MasterShikake::with(['conveyor'])->select('master_shikake.*');
    }

    public function getDatatable($areaId = null, $conveyorId = null, $process = null)
    {
        $query = MasterShikake::select([
                'master_shikake.id',
                'master_shikake.conveyor_id',
                'master_shikake.process',
                'master_shikake.conveyor as shikake_conveyor',
                'master_shikake.machine',
                'master_shikake.qty',
                'master_shikake.issue',
                'master_shikake.barcode_kanban',
                'master_shikake.family',
                'master_shikake.sequence',
                'master_conveyor.conveyor',
                'master_conveyor.master_area_id',
                'master_area.area',
                // Add identifier columns from child tables
                'master_shikake_twist.cct_no as twist_cct_no',
                'master_shikake_bonder.bonder_no',
                'master_shikake_joint.bonder_no as joint_bonder_no', 
                'master_shikake_shield.shield_no',
                'master_shikake_dbl_crimp.shield_no as dbl_crimp_shield_no'
            ])
            ->leftJoin('master_conveyor', 'master_shikake.conveyor_id', '=', 'master_conveyor.id')
            ->leftJoin('master_area', 'master_conveyor.master_area_id', '=', 'master_area.id')
            // Left join all child tables for identifier
            ->leftJoin('master_shikake_twist', 'master_shikake.id', '=', 'master_shikake_twist.master_shikake_id')
            ->leftJoin('master_shikake_bonder', 'master_shikake.id', '=', 'master_shikake_bonder.master_shikake_id')
            ->leftJoin('master_shikake_joint', 'master_shikake.id', '=', 'master_shikake_joint.master_shikake_id')
            ->leftJoin('master_shikake_shield', 'master_shikake.id', '=', 'master_shikake_shield.master_shikake_id')
            ->leftJoin('master_shikake_dbl_crimp', 'master_shikake.id', '=', 'master_shikake_dbl_crimp.master_shikake_id');

        // Apply filters
        if ($areaId) {
            $query->where('master_area.id', $areaId);
        }

        if ($conveyorId) {
            $query->where('master_conveyor.id', $conveyorId);
        }

        if ($process) {
            $query->where('master_shikake.process', $process);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('conveyor_name', function ($row) {
                return $row->conveyor ?? '-';
            })
            ->addColumn('identifier', function ($row) {
                return $this->getIdentifierByProcess($row);
            })
            ->addColumn('process', function ($row) {
                $process = $row->process ?? '-';
                $badgeClass = match($process) {
                    'TWIST' => 'bg-primary',
                    'BONDER' => 'bg-success',
                    'JOINT' => 'bg-info',
                    'SHIELD' => 'bg-warning',
                    'DBL CRIMP' => 'bg-secondary',
                    default => 'bg-dark'
                };
                return '<span class="badge ' . $badgeClass . '">' . $process . '</span>';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                // View button
                if ($currentUser && $currentUser->hasMenuPermission('master_shikake', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-info btn-sm btn-view" data-id="' . $row->id . '" title="View"><i class="ti ti-eye"></i></button>';
                    $hasActions = true;
                }

                // Delete button
                if ($currentUser && $currentUser->hasMenuPermission('master_shikake', 'can_delete')) {
                    $actions .= '<button type="button" class="btn btn-soft-danger btn-sm btn-delete" data-id="' . $row->id . '" data-name="' . htmlspecialchars($row->barcode_kanban ?? '-', ENT_QUOTES) . '" data-barcode="' . htmlspecialchars($row->barcode_kanban ?? '-', ENT_QUOTES) . '" title="Delete"><i class="ti ti-trash"></i></button>';
                    $hasActions = true;
                }

                $actions .= '</div>';
                return $hasActions ? $actions : '-';
            })
            ->rawColumns(['action', 'process'])
            ->make(true);
    }

    /**
     * Get identifier based on process type
     */
    private function getIdentifierByProcess($row)
    {
        return match($row->process) {
            'TWIST' => $row->twist_cct_no ?? '-',
            'BONDER' => $row->bonder_no ?? '-',
            'JOINT' => $row->joint_bonder_no ?? '-',
            'SHIELD' => $row->shield_no ?? '-',
            'DBL CRIMP' => $row->dbl_crimp_shield_no ?? '-',
            default => '-'
        };
    }

    public function findById($id)
    {
        return MasterShikake::with(['conveyor', 'twists', 'bonders', 'joints', 'shields', 'dblCrimps'])->findOrFail($id);
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
        // Select the appropriate importer based on process type
        $importer = $this->getImporter($conveyorId, $process);
        return $importer->import($filePath, $startRow);
    }

    /**
     * Get the appropriate importer based on process type
     */
    protected function getImporter($conveyorId, $process)
    {
        return match ($process) {
            'TWIST' => new MasterShikakeTwistImport($conveyorId, $process),
            'BONDER' => new MasterShikakeBonderImport($conveyorId, $process),
            'JOINT' => new MasterShikakeJointImport($conveyorId, $process),
            'SHIELD' => new MasterShikakeShieldImport($conveyorId, $process),
            'DBL CRIMP' => new MasterShikakeDblCrimpImport($conveyorId, $process),
            default => throw new \Exception("Invalid process type: {$process}. Supported types: TWIST, BONDER, JOINT, SHIELD, DBL CRIMP"),
        };
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
