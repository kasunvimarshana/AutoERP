<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('roles.view'), 403);

        $perPage = min((int) $request->query('per_page', 15), 100);
        $roles = Role::where('guard_name', 'api')
            ->with('permissions')
            ->paginate($perPage);

        return response()->json($roles);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('roles.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'api']);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('roles.update'), 403);

        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:roles,name,'.$id],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('roles.delete'), 403);

        $role = Role::findOrFail($id);

        abort_if($role->name === 'super-admin', 422, 'Cannot delete the super-admin role.');

        $role->delete();

        return response()->json(null, 204);
    }

    public function syncPermissions(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('roles.update'), 403);

        $role = Role::findOrFail($id);

        $data = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions']);

        return response()->json($role->load('permissions'));
    }

    public function permissions(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('roles.view'), 403);

        $permissions = Permission::where('guard_name', 'api')->get();

        return response()->json($permissions);
    }
}
