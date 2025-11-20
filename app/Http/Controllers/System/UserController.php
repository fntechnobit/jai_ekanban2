<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\UserGroup;
use App\Services\UserService;
use App\Http\Requests\UserRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $groups = UserGroup::where('is_active', 1)->orderBy('name')->get();
        return view('system.user.index', compact('groups'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            return $this->userService->getDatatable();
        }
    }

    public function create()
    {
        return ResponseHelper::success();
    }

    public function store(UserRequest $request)
    {
        try {
            $user = $this->userService->create($request->validated());
            return ResponseHelper::success($user, 'User created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        $user = $this->userService->findById($id);
        return ResponseHelper::success($user);
    }

    public function edit($id)
    {
        $user = $this->userService->findById($id);
        return ResponseHelper::success($user);
    }

    public function update(UserRequest $request, $id)
    {
        try {
            $user = $this->userService->findById($id);
            $this->userService->update($user, $request->validated());
            return ResponseHelper::success($user, 'User updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $user = $this->userService->findById($id);
            $this->userService->delete($user);
            return ResponseHelper::success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
