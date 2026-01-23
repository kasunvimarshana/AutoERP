<?php

namespace App\Modules\UserManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\UserManagement\Services\UserService;
use App\Modules\UserManagement\Http\Requests\StoreUserRequest;
use App\Modules\UserManagement\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'tenant_id' => $request->input('tenant_id'),
                'search' => $request->input('search'),
                'role' => $request->input('role'),
                'status' => $request->input('status'),
                'per_page' => $request->input('per_page', 15),
            ];

            $users = $this->userService->search($criteria);
            return $this->success($users);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());
            return $this->created($user, 'User created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->findById($id);
            
            if (!$user) {
                return $this->notFound('User not found');
            }

            // Load relationships
            $user->load(['tenant', 'roles', 'permissions']);

            return $this->success($user);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->userService->update($id, $request->validated());
            return $this->success($user, 'User updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->delete($id);
            return $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function activate(int $id): JsonResponse
    {
        try {
            $user = $this->userService->activate($id);
            return $this->success($user, 'User activated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function deactivate(int $id): JsonResponse
    {
        try {
            $user = $this->userService->deactivate($id);
            return $this->success($user, 'User deactivated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function assignRoles(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'roles' => 'required|array',
                'roles.*' => 'string|exists:roles,name',
            ]);

            $user = $this->userService->assignRoles($id, $request->input('roles'));
            return $this->success($user, 'Roles assigned successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            $user = $this->userService->assignPermissions($id, $request->input('permissions'));
            return $this->success($user, 'Permissions assigned successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
