<?php

namespace App\Services;

use App\Models\MasterFamily;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MasterFamilyService
{
    public function getAll()
    {
        return MasterFamily::select('master_family.*');
    }

    public function getDatatable()
    {
        $data = $this->getAll();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = [];

                if ($currentUser && $currentUser->hasMenuPermission('master_family', 'can_update')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' . $row->id . '" title="Edit"><i class="fas fa-edit"></i></button>';
                }

                if ($currentUser && $currentUser->hasMenuPermission('master_family', 'can_delete')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                }

                return !empty($actions) ? implode(' ', $actions) : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create(array $data)
    {
        $data['created_by'] = Auth::id();
        return MasterFamily::create($data);
    }

    public function update(MasterFamily $masterFamily, array $data)
    {
        $data['updated_by'] = Auth::id();
        $masterFamily->update($data);
        return $masterFamily;
    }

    public function delete(MasterFamily $masterFamily)
    {
        $masterFamily->deleted_by = Auth::id();
        $masterFamily->save();
        $masterFamily->delete();
        return true;
    }

    public function findById($id)
    {
        return MasterFamily::findOrFail($id);
    }
}
