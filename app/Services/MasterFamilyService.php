<?php

namespace App\Services;

use App\Models\MasterFamily;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MasterFamilyService
{
    public function getAll()
    {
        return MasterFamily::with(['area', 'carline'])->select('master_family.*');
    }

    public function getDatatable()
    {
        $data = $this->getAll();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('area_name', function ($row) {
                return $row->area->area ?? '-';
            })
            ->addColumn('carline_code', function ($row) {
                return $row->carline ? $row->carline->code : '-';
            })
            ->addColumn('carline_name', function ($row) {
                return $row->carline ? $row->carline->name : '-';
            })
            ->addColumn('action', function ($row) {
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                if ($currentUser && $currentUser->hasMenuPermission('master_family', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-primary btn-sm btn-edit" data-id="' . $row->id . '" title="Edit"><i class="ti ti-pencil"></i></button>';
                    $hasActions = true;
                }

                if ($currentUser && $currentUser->hasMenuPermission('master_family', 'can_delete')) {
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
        $data = $this->mapArea($data);
        $data['created_by'] = Auth::id();
        return MasterFamily::create($data);
    }

    public function update(MasterFamily $masterFamily, array $data)
    {
        $data = $this->mapArea($data);
        $data['updated_by'] = Auth::id();
        $masterFamily->update($data);
        return $masterFamily;
    }

    /**
     * The form posts the area as `area_id`; the column is `master_area_id`.
     */
    private function mapArea(array $data): array
    {
        if (array_key_exists('area_id', $data)) {
            $data['master_area_id'] = $data['area_id'];
            unset($data['area_id']);
        }

        return $data;
    }

    public function delete(MasterFamily $masterFamily)
    {
        $masterFamily->deleted_by = Auth::id();
        $masterFamily->save();
        $masterFamily->delete();
        MasterFamily::forgetEnsured();
        return true;
    }

    public function findById($id)
    {
        return MasterFamily::with(['area', 'carline'])->findOrFail($id);
    }
}
