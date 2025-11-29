<?php

namespace App\Services;

use App\Models\MasterArea;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MasterAreaService
{
    public function getAll()
    {
        return MasterArea::select('master_area.*');
    }

    public function getDatatable()
    {
        $data = $this->getAll();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('status', function ($row) {
                return '<span class="badge bg-success">Active</span>';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = [];

                if ($currentUser && $currentUser->hasMenuPermission('master_area', 'can_update')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' . $row->id . '" title="Edit"><i class="fas fa-edit"></i></button>';
                }

                if ($currentUser && $currentUser->hasMenuPermission('master_area', 'can_delete')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                }

                return !empty($actions) ? implode(' ', $actions) : '-';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function create(array $data)
    {
        $data['created_by'] = Auth::id();
        return MasterArea::create($data);
    }

    public function update(MasterArea $masterArea, array $data)
    {
        $data['updated_by'] = Auth::id();
        $masterArea->update($data);
        return $masterArea;
    }

    public function delete(MasterArea $masterArea)
    {
        $masterArea->deleted_by = Auth::id();
        $masterArea->save();
        $masterArea->delete();
        return true;
    }

    public function findById($id)
    {
        return MasterArea::findOrFail($id);
    }
}
