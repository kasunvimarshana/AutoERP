<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IAM\Http\Requests\AssignPermissionsRequest;
use Modules\IAM\Http\Requests\CreatePermissionRequest;
use Modules\IAM\Http\Requests\UpdatePermissionRequest;
use Modules\IAM\Http\Resources\PermissionResource;
use Modules\IAM\Repositories\Contracts\PermissionRepositoryInterface;
use Modules\IAM\Services\PermissionService;

/**
 * Permission Controller
 *
 * Handles HTTP requests for permission management.
 * Uses native Laravel features only.
 */
class PermissionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService,
        private PermissionRepositoryInterface $permissionRepository
    ) {}

    /**
     * Display a listing of permissions.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $permissions = $this->permissionRepository->paginate($perPage);

        return response()->json(PermissionResource::collection($permissions));
    }

    /**
     * Store a newly created permission.
     */
    public function store(CreatePermissionRequest $request): JsonResponse
    {
        try {
            $permission = $this->permissionService->createPermission($request->validated());

            return response()->json(
                new PermissionResource($permission),
                201
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create permission',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified permission.
     */
    public function show(int $id): JsonResponse
    {
        $permission = $this->permissionRepository->findById($id);

        if (! $permission) {
            return response()->json([
                'message' => 'Permission not found',
            ], 404);
        }

        return response()->json(new PermissionResource($permission));
    }

    /**
     * Update the specified permission.
     */
    public function update(UpdatePermissionRequest $request, int $id): JsonResponse
    {
        try {
            $permission = $this->permissionService->updatePermission($id, $request->validated());

            return response()->json(new PermissionResource($permission));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update permission',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->permissionService->deletePermission($id);

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete permission',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign permissions to a role.
     */
    public function assignToRole(AssignPermissionsRequest $request, int $roleId): JsonResponse
    {
        try {
            $this->permissionService->assignPermissionsToRole(
                $roleId,
                $request->input('permission_ids')
            );

            return response()->json([
                'message' => 'Permissions assigned successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign permissions',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign permissions directly to a user.
     */
    public function assignToUser(AssignPermissionsRequest $request, int $userId): JsonResponse
    {
        try {
            $this->permissionService->assignPermissionsToUser(
                $userId,
                $request->input('permission_ids'),
                $request->input('type', 'grant')
            );

            return response()->json([
                'message' => 'Permissions assigned successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign permissions',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get active permissions only.
     */
    public function active(): JsonResponse
    {
        $permissions = $this->permissionRepository->findActive();

        return response()->json(PermissionResource::collection($permissions));
    }

    /**
     * Get permissions by module.
     */
    public function byModule(string $module): JsonResponse
    {
        $permissions = $this->permissionRepository->findByModule($module);

        return response()->json(PermissionResource::collection($permissions));
    }

    /**
     * Search permissions.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $permissions = $this->permissionRepository->search($query);

        return response()->json(PermissionResource::collection($permissions));
    }

    /**
     * Generate CRUD permissions for a resource.
     */
    public function generateCrud(Request $request): JsonResponse
    {
        $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'resource' => ['required', 'string', 'max:100'],
        ]);

        try {
            $permissions = $this->permissionService->generateCrudPermissions(
                $request->input('module'),
                $request->input('resource')
            );

            return response()->json([
                'message' => 'CRUD permissions generated successfully',
                'data' => PermissionResource::collection($permissions),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate permissions',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
