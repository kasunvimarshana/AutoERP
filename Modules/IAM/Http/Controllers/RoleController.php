<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IAM\Entities\Role;
use Modules\IAM\Http\Requests\AssignPermissionsToRoleRequest;
use Modules\IAM\Http\Requests\CreateRoleRequest;
use Modules\IAM\Http\Requests\UpdateRoleRequest;
use Modules\IAM\Http\Resources\RoleResource;
use Modules\IAM\Services\RoleService;

/**
 * Role Controller
 *
 * Handles HTTP requests for role management using native Laravel features.
 */
class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $roles = Role::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->active())
            ->when($request->boolean('system_only'), fn ($q) => $q->system())
            ->when($request->boolean('custom_only'), fn ($q) => $q->custom())
            ->when($request->has('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->with(['parent', 'rolePermissions'])
            ->orderBy('level')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json(RoleResource::collection($roles));
    }

    /**
     * Store a newly created role.
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        try {
            $role = $this->roleService->createRole($request->validated());

            return response()->json(new RoleResource($role), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create role',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['parent', 'children', 'rolePermissions', 'users']);

        return response()->json(new RoleResource($role));
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        try {
            $role = $this->roleService->updateRole($role, $request->validated());

            return response()->json(new RoleResource($role));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update role',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            $this->roleService->deleteRole($role);

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete role',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissions(AssignPermissionsToRoleRequest $request, Role $role): JsonResponse
    {
        try {
            $this->roleService->assignPermissions($role, $request->input('permission_ids'));

            return response()->json([
                'message' => 'Permissions assigned successfully',
                'role' => new RoleResource($role->load('rolePermissions')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign permissions',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all permissions for a role including inherited.
     */
    public function permissions(Role $role): JsonResponse
    {
        return response()->json([
            'direct_permissions' => $role->rolePermissions()->active()->get(),
            'all_permissions' => $role->getAllPermissions(),
        ]);
    }

    /**
     * Get all users assigned to a role.
     */
    public function users(Role $role): JsonResponse
    {
        $users = $role->users()->with('roles')->paginate(15);

        return response()->json($users);
    }
}
