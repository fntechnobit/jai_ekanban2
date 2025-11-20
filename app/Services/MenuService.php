<?php

namespace App\Services;

use App\Models\Menu;
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
                $btn = '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="'.$row->id.'" title="Edit">';
                $btn .= '<i class="fas fa-edit"></i></button> ';
                $btn .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'" title="Delete">';
                $btn .= '<i class="fas fa-trash"></i></button>';
                return $btn;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function getParentMenus()
    {
        return Menu::whereNull('parent_id')->orderBy('order')->get();
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
