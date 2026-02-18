<?php

namespace Modules\IAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\IAM\DTOs\RoleDTO;
use Modules\IAM\Http\Requests\StoreRoleRequest;
use Modules\IAM\Http\Requests\UpdateRoleRequest;
use Modules\IAM\Http\Resources\RoleResource;
use Modules\IAM\Http\Resources\PermissionResource;
use Modules\IAM\Services\RoleService;

class RoleController extends BaseController
{
    public function __construct(private RoleService $roleService) {}

    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="List all roles",
     *     description="Retrieve all roles in the system",
     *     operationId="rolesIndex",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Role")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $roles = $this->roleService->getAll();

            return $this->success(RoleResource::collection($roles));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch roles: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Create a new role",
     *     description="Create a new role with optional permissions and parent role",
     *     operationId="rolesStore",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Role creation data",
     *         @OA\JsonContent(ref="#/components/schemas/StoreRoleRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Role")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $dto = new RoleDTO($request->validated());
            $role = $this->roleService->create($dto);

            return $this->created(RoleResource::make($role), 'Role created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create role: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/roles/{id}",
     *     summary="Get role by ID",
     *     description="Retrieve a specific role by its ID",
     *     operationId="rolesShow",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Role")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $role = $this->roleService->find($id);

            if (! $role) {
                return $this->notFound('Role not found');
            }

            return $this->success(RoleResource::make($role));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch role: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/roles/{id}",
     *     summary="Update role",
     *     description="Update an existing role's information",
     *     operationId="rolesUpdate",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Role update data",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateRoleRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Role")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        try {
            $dto = new RoleDTO($request->validated());
            $dto->id = $id;
            $role = $this->roleService->update($id, $dto);

            return $this->updated(RoleResource::make($role), 'Role updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update role: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Delete role",
     *     description="Delete a role from the system",
     *     operationId="rolesDestroy",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->roleService->delete($id);

            return $this->deleted('Role deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete role: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/roles/{id}/permissions/assign",
     *     summary="Assign permissions to role",
     *     description="Assign one or more permissions to a role",
     *     operationId="rolesAssignPermissions",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Permissions assignment data",
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="user.create"),
     *                 description="Array of permission names to assign"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions assigned successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Role")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permissions' => ['required', 'array'],
                'permissions.*' => ['string', 'exists:permissions,name'],
            ]);

            $role = $this->roleService->assignPermissions($id, $validated['permissions']);

            return $this->success(RoleResource::make($role), 'Permissions assigned successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to assign permissions: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/roles/{id}/permissions/revoke",
     *     summary="Revoke permissions from role",
     *     description="Revoke one or more permissions from a role",
     *     operationId="rolesRevokePermissions",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Permissions revocation data",
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="user.delete"),
     *                 description="Array of permission names to revoke"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions revoked successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Role")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function revokePermissions(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permissions' => ['required', 'array'],
                'permissions.*' => ['string', 'exists:permissions,name'],
            ]);

            $role = $this->roleService->revokePermissions($id, $validated['permissions']);

            return $this->success(RoleResource::make($role), 'Permissions revoked successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to revoke permissions: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/roles/{id}/permissions/sync",
     *     summary="Synchronize role permissions",
     *     description="Replace all role permissions with the specified set of permissions",
     *     operationId="rolesSyncPermissions",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Permissions synchronization data",
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="user.view"),
     *                 description="Array of permission names (all existing permissions will be replaced with these)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions synchronized successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions synchronized successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Role")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permissions' => ['required', 'array'],
                'permissions.*' => ['string', 'exists:permissions,name'],
            ]);

            $role = $this->roleService->syncPermissions($id, $validated['permissions']);

            return $this->success(RoleResource::make($role), 'Permissions synchronized successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to sync permissions: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/roles/hierarchy",
     *     summary="Get role hierarchy",
     *     description="Retrieve the hierarchical structure of all roles",
     *     operationId="rolesHierarchy",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Role hierarchy retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Hierarchical structure of roles"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function hierarchy(): JsonResponse
    {
        try {
            $hierarchy = $this->roleService->getHierarchy();

            return $this->success($hierarchy);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch role hierarchy: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/roles/{id}/permissions",
     *     summary="Get role permissions",
     *     description="Retrieve all permissions assigned to a specific role",
     *     operationId="rolesPermissions",
     *     tags={"IAM-Roles"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Permission")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function permissions(int $id): JsonResponse
    {
        try {
            $permissions = $this->roleService->getAllPermissions($id);

            return $this->success(PermissionResource::collection($permissions));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch role permissions: '.$e->getMessage(), 500);
        }
    }
}
