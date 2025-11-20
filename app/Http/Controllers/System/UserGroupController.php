<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\UserGroupService;
use App\Http\Requests\UserGroupRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    protected $userGroupService;

    public function __construct(UserGroupService $userGroupService)
    {
        $this->userGroupService = $userGroupService;
    }

    public function index()
    {
        return view('system.user_group.index');
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            return $this->userGroupService->getDatatable();
        }
    }

    public function create()
    {
        return ResponseHelper::success();
    }

    public function store(UserGroupRequest $request)
    {
        try {
            $group = $this->userGroupService->create($request->validated());
            return ResponseHelper::success($group, 'User group created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $group = $this->userGroupService->findById($id);
        return ResponseHelper::success($group);
    }

    public function edit($id)
    {
        $group = $this->userGroupService->findById($id);
        return ResponseHelper::success($group);
    }

    public function update(UserGroupRequest $request, $id)
    {
        try {
            $group = $this->userGroupService->findById($id);
            $this->userGroupService->update($group, $request->validated());
            return ResponseHelper::success($group, 'User group updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $group = $this->userGroupService->findById($id);
            $this->userGroupService->delete($group);
            return ResponseHelper::success(null, 'User group deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function permissions($id)
    {
        try {
            $data = $this->userGroupService->getPermissions($id);
            return ResponseHelper::success($data);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function updatePermissions(Request $request, $id)
    {
        try {
            $permissions = $request->input('permissions', []);
            $this->userGroupService->updatePermissions($id, $permissions);
            return ResponseHelper::success(null, 'Permissions updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
