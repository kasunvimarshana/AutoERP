<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\IAM\DTOs\RoleDTO;
use App\Modules\IAM\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Role API Controller
 *
 * Handles HTTP requests for role management.
 */
class RoleController extends BaseController
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * Display a listing of roles
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $tenantId = $request->input('tenant_id');

        if ($tenantId) {
            $roles = $this->roleService->getAllRoles($tenantId);

            return $this->successResponse($roles, 'Roles retrieved successfully');
        }

        $roles = $this->roleService->getPaginatedRoles($perPage);

        return $this->successResponse($roles, 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'is_system_role' => 'boolean',
        ]);

        try {
            $dto = RoleDTO::fromArray($validated);
            $role = $this->roleService->createRole($dto);

            return $this->createdResponse($role, 'Role created successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Display the specified role
     */
    public function show(int $id): JsonResponse
    {
        try {
            $role = $this->roleService->getRoleById($id);

            // Load relationships
            $role->load(['permissions', 'tenant']);

            return $this->successResponse($role, 'Role retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Role not found');
        }
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:roles,slug,'.$id,
            'description' => 'nullable|string',
        ]);

        try {
            $role = $this->roleService->updateRole($id, $validated);

            return $this->successResponse($role, 'Role updated successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return $this->notFoundResponse('Role not found');
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->roleService->deleteRole($id);

            return $this->successResponse(null, 'Role deleted successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return $this->notFoundResponse('Role not found');
        }
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        try {
            $role = $this->roleService->assignPermissions($id, $validated['permission_ids']);

            return $this->successResponse($role, 'Permissions assigned successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Sync role permissions
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        try {
            $role = $this->roleService->syncPermissions($id, $validated['permission_ids']);

            return $this->successResponse($role, 'Role permissions synchronized successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Get role permissions
     */
    public function getPermissions(int $id): JsonResponse
    {
        try {
            $permissions = $this->roleService->getRolePermissions($id);

            return $this->successResponse($permissions, 'Role permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Role not found');
        }
    }

    /**
     * Get role users
     */
    public function getUsers(int $id): JsonResponse
    {
        try {
            $users = $this->roleService->getRoleUsers($id);

            return $this->successResponse($users, 'Role users retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Role not found');
        }
    }

    /**
     * Get system roles
     */
    public function systemRoles(): JsonResponse
    {
        $roles = $this->roleService->getSystemRoles();

        return $this->successResponse($roles, 'System roles retrieved successfully');
    }

    /**
     * Get custom roles for tenant
     */
    public function customRoles(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return $this->errorResponse('tenant_id is required', null, 422);
        }

        $roles = $this->roleService->getCustomRoles($tenantId);

        return $this->successResponse($roles, 'Custom roles retrieved successfully');
    }
}
