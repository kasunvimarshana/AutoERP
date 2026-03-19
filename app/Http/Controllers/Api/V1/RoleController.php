<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

final class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/roles",
     *     summary="List all roles",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'success' => true,
            'data'    => RoleResource::collection($roles),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/roles",
     *     summary="Create a new role",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:125'],
            'guard_name'  => ['sometimes', 'string', 'max:125'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role = Role::create([
            'name'       => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'api',
        ]);

        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return response()->json([
            'success' => true,
            'data'    => new RoleResource($role->load('permissions')),
            'message' => 'Role created successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/roles/{roleId}",
     *     summary="Update role permissions",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function update(Request $request, int $roleId): JsonResponse
    {
        /** @var Role|null $role */
        $role = Role::find($roleId);

        if ($role === null) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'ROLE_NOT_FOUND', 'message' => 'Role not found.'],
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = Permission::whereIn('name', $validated['permissions'])->get();
        $role->syncPermissions($permissions);

        return response()->json([
            'success' => true,
            'data'    => new RoleResource($role->load('permissions')),
            'message' => 'Role permissions updated.',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/permissions",
     *     summary="List all permissions",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $permissions->map(fn (Permission $p): array => [
                'id'         => $p->id,
                'name'       => $p->name,
                'guard_name' => $p->guard_name,
            ])->values(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/permissions",
     *     summary="Create a new permission",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function createPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:125'],
            'guard_name' => ['sometimes', 'string', 'max:125'],
        ]);

        $permission = Permission::firstOrCreate([
            'name'       => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'api',
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $permission->id,
                'name'       => $permission->name,
                'guard_name' => $permission->guard_name,
            ],
            'message' => 'Permission created.',
        ], Response::HTTP_CREATED);
    }
}
