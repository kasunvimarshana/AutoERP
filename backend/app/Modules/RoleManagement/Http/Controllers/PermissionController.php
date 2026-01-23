<?php

namespace App\Modules\RoleManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\RoleManagement\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionController extends BaseController
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getAllPermissions();
            return $this->success($permissions);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:permissions,name|max:255',
                'guard_name' => 'nullable|string|max:255',
            ]);

            $permission = $this->permissionService->create($request->all());
            return $this->created($permission, 'Permission created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $permission = $this->permissionService->findById($id);
            return $this->success($permission);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|unique:permissions,name,' . $id . '|max:255',
            ]);

            $permission = $this->permissionService->update($id, $request->all());
            return $this->success($permission, 'Permission updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->permissionService->delete($id);
            return $this->success(null, 'Permission deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function grouped(): JsonResponse
    {
        try {
            $grouped = $this->permissionService->getGroupedPermissions();
            return $this->success($grouped);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
