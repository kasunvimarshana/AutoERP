<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    // -------------------------------------------------------------------------
    // GET /api/roles
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');

        // Tenant scope if applicable
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            });
        }

        if ($request->has('per_page')) {
            $roles = $query->paginate((int) $request->input('per_page', 15));

            return $this->paginatedResponse($roles, 'Roles retrieved');
        }

        return $this->successResponse($query->get(), 'Roles retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/roles
    // -------------------------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:100', 'unique:roles,name'],
            'display_name'   => ['nullable', 'string', 'max:150'],
            'description'    => ['nullable', 'string'],
            'tenant_id'      => ['nullable', 'integer', 'exists:tenants,id'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name'         => $data['name'],
            'display_name' => $data['display_name'] ?? null,
            'description'  => $data['description']  ?? null,
            'tenant_id'    => $data['tenant_id']     ?? $request->header('X-Tenant-ID'),
        ]);

        if (! empty($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return $this->createdResponse($role->load('permissions'), 'Role created');
    }

    // -------------------------------------------------------------------------
    // GET /api/roles/{id}
    // -------------------------------------------------------------------------

    public function show(int|string $id): JsonResponse
    {
        $role = Role::with(['permissions', 'users'])->find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        return $this->successResponse($role, 'Role retrieved');
    }

    // -------------------------------------------------------------------------
    // PUT/PATCH /api/roles/{id}
    // -------------------------------------------------------------------------

    public function update(Request $request, int|string $id): JsonResponse
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:100', 'unique:roles,name,' . $id],
            'display_name'   => ['nullable', 'string', 'max:150'],
            'description'    => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->fill([
            'name'         => $data['name']         ?? $role->name,
            'display_name' => $data['display_name'] ?? $role->display_name,
            'description'  => $data['description']  ?? $role->description,
        ])->save();

        if (array_key_exists('permission_ids', $data)) {
            $role->permissions()->sync($data['permission_ids'] ?? []);
        }

        return $this->successResponse($role->load('permissions'), 'Role updated');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/roles/{id}
    // -------------------------------------------------------------------------

    public function destroy(int|string $id): JsonResponse
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        return $this->successResponse(null, 'Role deleted');
    }

    // -------------------------------------------------------------------------
    // GET /api/permissions  – list all available permissions
    // -------------------------------------------------------------------------

    public function permissions(Request $request): JsonResponse
    {
        $permissions = Permission::query()
            ->when($request->input('group'), fn ($q, $g) => $q->where('group', $g))
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        return $this->successResponse($permissions, 'Permissions retrieved');
    }
}
