<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\IAM;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends BaseController
{
    /**
     * Display a listing of roles.
     *
     * @OA\Get(
     *     path="/api/v1/roles",
     *     tags={"Roles"},
     *     summary="Get list of roles",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Get roles with permissions for current guard
        $roles = Role::with('permissions')
            ->orderBy('name')
            ->get();

        return $this->successResponse($roles);
    }

    /**
     * Store a newly created role.
     *
     * @OA\Post(
     *     path="/api/v1/roles",
     *     tags={"Roles"},
     *     summary="Create a new role",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="guard_name", type="string", default="web"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        try {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web',
            ]);

            // Assign permissions if provided
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            $role->load('permissions');

            return $this->successResponse(
                $role,
                'Role created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create role',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified role.
     *
     * @OA\Get(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Get role details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->errorResponse('Role not found', [], 404);
        }

        return $this->successResponse($role);
    }

    /**
     * Update the specified role.
     *
     * @OA\Put(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Update role",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->errorResponse('Role not found', [], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:roles,name,' . $id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        try {
            // Update role name if provided
            if ($request->has('name')) {
                $role->update(['name' => $request->name]);
            }

            // Sync permissions if provided
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            $role->load('permissions');

            return $this->successResponse(
                $role,
                'Role updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update role',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Remove the specified role.
     *
     * @OA\Delete(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Delete role",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->errorResponse('Role not found', [], 404);
        }

        try {
            // Check if role has users
            if ($role->users()->count() > 0) {
                return $this->errorResponse(
                    'Cannot delete role',
                    ['Role is assigned to one or more users'],
                    422
                );
            }

            $role->delete();

            return $this->successResponse(
                null,
                'Role deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete role',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Assign permissions to role.
     *
     * @OA\Post(
     *     path="/api/v1/roles/{id}/permissions",
     *     tags={"Roles"},
     *     summary="Assign permissions to role",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions assigned successfully"
     *     )
     * )
     */
    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->errorResponse('Role not found', [], 404);
        }

        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        try {
            $role->syncPermissions($request->permissions);
            $role->load('permissions');

            return $this->successResponse(
                $role,
                'Permissions assigned successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to assign permissions',
                [$e->getMessage()],
                500
            );
        }
    }
}
