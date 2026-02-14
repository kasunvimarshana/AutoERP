<?php

namespace App\Modules\Role\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Role\Services\RoleService;
use App\Modules\Role\DTOs\RoleDTO;
use App\Modules\Role\Http\Requests\StoreRoleRequest;
use App\Modules\Role\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Tag(
 *     name="Roles",
 *     description="Role management endpoints"
 * )
 */
class RoleController extends BaseController
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles",
     *     tags={"Roles"},
     *     summary="List all roles",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $roles = $this->roleService->paginate($perPage, ['permissions', 'tenant']);
            
            return $this->successResponse($roles, 'Roles retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/roles",
     *     tags={"Roles"},
     *     summary="Create a new role",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "tenant_id"},
     *             @OA\Property(property="name", type="string", example="manager"),
     *             @OA\Property(property="guard_name", type="string", example="web"),
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="users.view")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     * 
     * @param StoreRoleRequest $request
     */
    public function store(FormRequest $request): JsonResponse
    {
        try {
            $dto = RoleDTO::fromArray($request->validated());
            $role = $this->roleService->createRole($dto);
            
            return $this->successResponse($role, 'Role created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Get role by ID",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
     *     ),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $role = $this->roleService->findById($id, ['permissions', 'tenant']);
            
            if (!$role) {
                return $this->errorResponse('Role not found', 404);
            }
            
            return $this->successResponse($role, 'Role retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Update role",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="manager"),
     *             @OA\Property(property="guard_name", type="string", example="web"),
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="users.view")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     * 
     * @param UpdateRoleRequest $request
     */
    public function update(FormRequest $request, string $id): JsonResponse
    {
        try {
            $dto = RoleDTO::fromArray($request->validated());
            $updated = $this->roleService->updateRole($id, $dto);
            
            if (!$updated) {
                return $this->errorResponse('Failed to update role', 500);
            }
            
            return $this->successResponse(null, 'Role updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Delete role",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully"
     *     ),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->roleService->deleteRole($id);
            
            if (!$deleted) {
                return $this->errorResponse('Failed to delete role', 500);
            }
            
            return $this->successResponse(null, 'Role deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/roles/{id}/permissions",
     *     tags={"Roles"},
     *     summary="Sync permissions for role",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="users.view")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions synced successfully"
     *     ),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function syncPermissions(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'permissions' => ['required', 'array'],
                'permissions.*' => ['string', 'exists:permissions,name'],
            ]);
            
            $result = $this->roleService->syncPermissions($id, $request->permissions);
            
            if (!$result) {
                return $this->errorResponse('Failed to sync permissions', 500);
            }
            
            return $this->successResponse(null, 'Permissions synced successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
