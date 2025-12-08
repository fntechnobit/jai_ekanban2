<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MenuService
{
    public function getAllWithParent()
    {
        return Menu::with('parent')->select('menus.*');
    }

    public function getDatatable()
    {
        $data = $this->getAllWithParent();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('parent_name', function($row){
                return $row->parent ? $row->parent->name : '-';
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

                if ($currentUser && $currentUser->hasMenuPermission('menus', 'can_update')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="'.$row->id.'" title="Edit"><i class="fas fa-edit"></i></button>';
                }

                if ($currentUser && $currentUser->hasMenuPermission('menus', 'can_delete')) {
                    $actions[] = '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'" title="Delete"><i class="fas fa-trash"></i></button>';
                }

                return !empty($actions) ? implode(' ', $actions) : '-';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function getParentMenus()
    {
        return Menu::whereNull('parent_id')->orderBy('order')->get();
    }

    /**
     * Get menus in hierarchical tree structure
     */
    public function getMenuTree()
    {
        $menus = Menu::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        return $this->formatMenuTree($menus);
    }

    /**
     * Format menu collection into tree structure for nested sortable
     */
    protected function formatMenuTree($menus)
    {
        return $menus->map(function($menu) {
            return [
                'id' => $menu->id,
                'code' => $menu->code,
                'name' => $menu->name,
                'url' => $menu->url,
                'icon' => $menu->icon,
                'order' => $menu->order,
                'is_active' => $menu->is_active,
                'children' => $menu->children->count() > 0 
                    ? $this->formatMenuTree($menu->children) 
                    : []
            ];
        })->toArray();
    }

    /**
     * Reorder menus based on drag-and-drop hierarchy
     */
    public function reorderMenus(array $items, $parentId = null, $order = 0)
    {
        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                Menu::where('id', $item['id'])->update([
                    'parent_id' => $parentId,
                    'order' => $order
                ]);
                $order++;

                // Process children recursively
                if (!empty($item['children'])) {
                    $this->reorderMenus($item['children'], $item['id']);
                }
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function create(array $data)
    {
        return Menu::create($data);
    }

    public function update(Menu $menu, array $data)
    {
        // Prevent circular parent relationship
        if (isset($data['parent_id']) && $data['parent_id'] == $menu->id) {
            throw new \Exception('Menu cannot be its own parent');
        }

        $menu->update($data);
        return $menu;
    }

    public function delete(Menu $menu)
    {
        // Check if menu has children
        if ($menu->children()->count() > 0) {
            throw new \Exception('Cannot delete menu with sub-menus');
        }

        $menu->delete();
        return true;
    }

    public function findById($id)
    {
        return Menu::findOrFail($id);
    }
}
