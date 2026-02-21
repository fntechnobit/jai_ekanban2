<?php

namespace App\Services;

use App\Models\MasterCarline;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MasterCarlineService
{
    public function getAll()
    {
        return MasterCarline::with('area')->select('master_carline.*');
    }

    public function getDatatable()
    {
        $data = $this->getAll();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('area_name', function ($row) {
                return $row->area ? $row->area->area : '-';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                if ($currentUser && $currentUser->hasMenuPermission('master_carline', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-primary btn-sm btn-edit" data-id="' . $row->id . '" title="Edit"><i class="ti ti-pencil"></i></button>';
                    $hasActions = true;
                }

                if ($currentUser && $currentUser->hasMenuPermission('master_carline', 'can_delete')) {
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
        $data['created_by'] = Auth::id();
        return MasterCarline::create($data);
    }

    public function update(MasterCarline $masterCarline, array $data)
    {
        $data['updated_by'] = Auth::id();
        $masterCarline->update($data);
        return $masterCarline;
    }

    public function delete(MasterCarline $masterCarline)
    {
        $masterCarline->deleted_by = Auth::id();
        $masterCarline->save();
        $masterCarline->delete();
        return true;
    }

    public function findById($id)
    {
        return MasterCarline::with('area')->findOrFail($id);
    }
}
