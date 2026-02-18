<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\IAM\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Permission API Controller
 *
 * Handles HTTP requests for permission management.
 * Note: Permissions are system-defined and read-only via API.
 */
class PermissionController extends BaseController
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Display a listing of permissions
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $module = $request->input('module');

        if ($module) {
            $permissions = $this->permissionService->getPermissionsByModule($module);

            return $this->successResponse($permissions, 'Permissions retrieved successfully');
        }

        if ($request->has('grouped')) {
            $permissions = $this->permissionService->getPermissionsGroupedByModule();

            return $this->successResponse($permissions, 'Permissions grouped by module retrieved successfully');
        }

        $permissions = $this->permissionService->getPaginatedPermissions($perPage);

        return $this->successResponse($permissions, 'Permissions retrieved successfully');
    }

    /**
     * Display the specified permission
     */
    public function show(int $id): JsonResponse
    {
        try {
            $permission = $this->permissionService->getPermissionById($id);

            // Load relationships
            $permission->load(['roles']);

            return $this->successResponse($permission, 'Permission retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Permission not found');
        }
    }

    /**
     * Get permission roles
     */
    public function getRoles(int $id): JsonResponse
    {
        try {
            $roles = $this->permissionService->getPermissionRoles($id);

            return $this->successResponse($roles, 'Permission roles retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Permission not found');
        }
    }

    /**
     * Get permissions grouped by module
     */
    public function grouped(): JsonResponse
    {
        $permissions = $this->permissionService->getPermissionsGroupedByModule();

        return $this->successResponse($permissions, 'Permissions grouped by module retrieved successfully');
    }
}
