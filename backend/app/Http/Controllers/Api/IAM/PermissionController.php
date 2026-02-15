<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\IAM;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends BaseController
{
    /**
     * Display a listing of permissions.
     *
     * @OA\Get(
     *     path="/api/v1/permissions",
     *     tags={"Permissions"},
     *     summary="Get list of permissions",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="group",
     *         in="query",
     *         description="Filter by permission group",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query()->orderBy('name');

        // Filter by group if provided (e.g., "products", "inventory")
        if ($request->has('group')) {
            $query->where('name', 'like', $request->group . '-%');
        }

        $permissions = $query->get();

        // Group permissions by module
        $grouped = $permissions->groupBy(function ($permission) {
            // Extract module name from permission (e.g., "view-products" -> "products")
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        });

        return $this->successResponse([
            'permissions' => $permissions,
            'grouped' => $grouped,
        ]);
    }

    /**
     * Store a newly created permission.
     *
     * @OA\Post(
     *     path="/api/v1/permissions",
     *     tags={"Permissions"},
     *     summary="Create a new permission",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="guard_name", type="string", default="web")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission created successfully"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $permission = Permission::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web',
            ]);

            return $this->successResponse(
                $permission,
                'Permission created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create permission',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified permission.
     *
     * @OA\Get(
     *     path="/api/v1/permissions/{id}",
     *     tags={"Permissions"},
     *     summary="Get permission details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
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
        $permission = Permission::with('roles')->find($id);

        if (!$permission) {
            return $this->errorResponse('Permission not found', [], 404);
        }

        return $this->successResponse($permission);
    }

    /**
     * Update the specified permission.
     *
     * @OA\Put(
     *     path="/api/v1/permissions/{id}",
     *     tags={"Permissions"},
     *     summary="Update permission",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission updated successfully"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->errorResponse('Permission not found', [], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:permissions,name,' . $id],
        ]);

        try {
            $permission->update($request->only('name'));

            return $this->successResponse(
                $permission,
                'Permission updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update permission',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Remove the specified permission.
     *
     * @OA\Delete(
     *     path="/api/v1/permissions/{id}",
     *     tags={"Permissions"},
     *     summary="Delete permission",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission deleted successfully"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->errorResponse('Permission not found', [], 404);
        }

        try {
            // Check if permission is assigned to roles
            if ($permission->roles()->count() > 0) {
                return $this->errorResponse(
                    'Cannot delete permission',
                    ['Permission is assigned to one or more roles'],
                    422
                );
            }

            $permission->delete();

            return $this->successResponse(
                null,
                'Permission deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete permission',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Get permissions by module.
     *
     * @OA\Get(
     *     path="/api/v1/permissions/by-module",
     *     tags={"Permissions"},
     *     summary="Get permissions grouped by module",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function byModule(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get();

        // Group by module
        $grouped = $permissions->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        })->map(function ($group) {
            return $group->pluck('name');
        });

        return $this->successResponse($grouped);
    }
}
