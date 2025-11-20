<?php

namespace App\Services;

use App\Models\UserGroup;
use App\Models\Menu;
use App\Models\GroupMenuAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class UserGroupService
{
    public function getAllWithCount()
    {
        return UserGroup::withCount('users')->select('user_groups.*');
    }

    public function getDatatable()
    {
        $data = $this->getAllWithCount();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('users_link', function($row){
                $count = $row->users_count ?? $row->users()->count();
                if ($count < 1) {
                    return '<span class="text-muted">0 Users</span>';
                }

                $url = route('system.users.index', ['group_id' => $row->id]);
                return '<a href="'.$url.'" target="_blank" class="badge badge-primary">'.$count.' Users</a>';
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

                if ($currentUser && $currentUser->hasMenuPermission('user_groups', 'can_update')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="'.$row->id.'" title="Edit"><i class="fas fa-edit"></i></button>';
                    $actions[] = '<button type="button" class="btn btn-sm btn-warning btn-permission" data-id="'.$row->id.'" title="Permissions"><i class="fas fa-key"></i></button>';
                }

                if ($currentUser && $currentUser->hasMenuPermission('user_groups', 'can_delete')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'" title="Delete"><i class="fas fa-trash"></i></button>';
                }

                return !empty($actions) ? implode(' ', $actions) : '-';
            })
            ->rawColumns(['status', 'action', 'users_link'])
            ->make(true);
    }

    public function create(array $data)
    {
        return UserGroup::create($data);
    }

    public function update(UserGroup $group, array $data)
    {
        $group->update($data);
        return $group;
    }

    public function delete(UserGroup $group)
    {
        // Check if group has users
        if ($group->users()->count() > 0) {
            throw new \Exception('Cannot delete group with active users');
        }

        $group->delete();
        return true;
    }

    public function findById($id)
    {
        return UserGroup::findOrFail($id);
    }

    public function getPermissions($groupId)
    {
        $group = UserGroup::findOrFail($groupId);
        $menus = Menu::with(['children'])->whereNull('parent_id')->orderBy('order')->get();
        $permissions = GroupMenuAccess::where('group_id', $groupId)->get()->keyBy('menu_id');

        return [
            'group' => $group,
            'menus' => $menus,
            'permissions' => $permissions
        ];
    }

    public function updatePermissions($groupId, array $permissions)
    {
        $group = UserGroup::findOrFail($groupId);

        DB::beginTransaction();
        try {
            // Delete existing permissions
            GroupMenuAccess::where('group_id', $groupId)->delete();

            // Insert new permissions
            foreach ($permissions as $menuId => $perms) {
                if (isset($perms['can_read']) && $perms['can_read']) {
                    GroupMenuAccess::create([
                        'group_id' => $groupId,
                        'menu_id' => $menuId,
                        'can_create' => $perms['can_create'] ?? false,
                        'can_read' => true,
                        'can_update' => $perms['can_update'] ?? false,
                        'can_delete' => $perms['can_delete'] ?? false,
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
