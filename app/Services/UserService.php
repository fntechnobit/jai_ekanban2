<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class UserService
{
    public function getAllWithGroups()
    {
        return User::with('group')->select('users.*');
    }

    public function getDatatable()
    {
        $data = $this->getAllWithGroups();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('group_name', function($row){
                return $row->group ? $row->group->name : '-';
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
                $actions = [];

                if ($currentUser && $currentUser->hasMenuPermission('users', 'can_update')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="'.$row->id.'" title="Edit"><i class="fas fa-edit"></i></button>';
                }

                if ($currentUser && $currentUser->hasMenuPermission('users', 'can_delete') && $currentUser->id !== $row->id) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'" title="Delete"><i class="fas fa-trash"></i></button>';
                }

                return !empty($actions) ? implode(' ', $actions) : '-';
            })
            ->rawColumns(['status', 'action'])
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
