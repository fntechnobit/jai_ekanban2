<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class UserService
{
    public function getAllWithGroups(?int $groupId = null)
    {
        $query = User::with('group')->select('users.*');

        if (!is_null($groupId)) {
            $query->where('group_id', $groupId);
        }

        return $query;
    }

    public function getDatatable()
    {
        $groupId = request()->filled('group_id') ? (int) request()->input('group_id') : null;

        $data = $this->getAllWithGroups($groupId);
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('group_label', function($row){
                if ($row->group) {
                    $badgeClass = $row->group->is_active ? 'badge badge-primary' : 'badge badge-secondary';
                    return '<span class="'.$badgeClass.'">'.$row->group->name.'</span>';
                }

                return '<span class="text-muted">Unassigned</span>';
            })
            ->addColumn('status', function($row){
                if ($row->is_active) {
                    return '<span class="badge bg-success">Active</span>';
                }
                return '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function($row){
                /** @var \App\Models\User|null $currentUser */
                $currentUser = Auth::user();
                $actions = '<div class="btn-group" role="group">';
                $hasActions = false;

                if ($currentUser && $currentUser->hasMenuPermission('users', 'can_update')) {
                    $actions .= '<button type="button" class="btn btn-soft-primary btn-sm btn-edit" data-id="'.$row->id.'" title="Edit"><i class="ti ti-pencil"></i></button>';
                    $hasActions = true;
                }

                if ($currentUser && $currentUser->hasMenuPermission('users', 'can_delete') && $currentUser->id !== $row->id) {
                    $actions .= '<button type="button" class="btn btn-soft-danger btn-sm btn-delete" data-id="'.$row->id.'" title="Delete"><i class="ti ti-trash"></i></button>';
                    $hasActions = true;
                }

                $actions .= '</div>';
                return $hasActions ? $actions : '-';
            })
            ->rawColumns(['status', 'action', 'group_label'])
            ->make(true);
    }

    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function update(User $user, array $data)
    {
        // Only update password if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return $user;
    }

    public function delete(User $user)
    {
        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            throw new \Exception('You cannot delete your own account');
        }

        $user->delete();
        return true;
    }

    public function findById($id)
    {
        return User::findOrFail($id);
    }
}
