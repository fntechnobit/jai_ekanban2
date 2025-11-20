<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use App\Http\Requests\MenuRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;

        $this->middleware('check.menu:menus,can_read')->only(['index', 'datatable', 'show']);
        $this->middleware('check.menu:menus,can_create')->only(['create', 'store']);
        $this->middleware('check.menu:menus,can_update')->only(['edit', 'update']);
        $this->middleware('check.menu:menus,can_delete')->only(['destroy']);
    }

    public function index()
    {
        $parentMenus = $this->menuService->getParentMenus();
        return view('system.menu.index', compact('parentMenus'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            return $this->menuService->getDatatable();
        }
    }

    public function create()
    {
        return ResponseHelper::success();
    }

    public function store(MenuRequest $request)
    {
        try {
            $menu = $this->menuService->create($request->validated());
            return ResponseHelper::success($menu, 'Menu created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $menu = $this->menuService->findById($id);
        return ResponseHelper::success($menu);
    }

    public function edit($id)
    {
        $menu = $this->menuService->findById($id);
        return ResponseHelper::success($menu);
    }

    public function update(MenuRequest $request, $id)
    {
        try {
            $menu = $this->menuService->findById($id);
            $this->menuService->update($menu, $request->validated());
            return ResponseHelper::success($menu, 'Menu updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $menu = $this->menuService->findById($id);
            $this->menuService->delete($menu);
            return ResponseHelper::success(null, 'Menu deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
