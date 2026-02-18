<?php

namespace App\Modules\IAM\Repositories;

use App\Modules\IAM\Models\Permission;
use App\Modules\IAM\Models\Role;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Role Repository
 *
 * Handles data access for roles.
 */
class RoleRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Role::class;
    }

    /**
     * Find role by slug
     */
    public function findBySlug(string $slug): ?Role
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get roles by tenant
     */
    public function getByTenant(int $tenantId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    /**
     * Get system roles
     */
    public function getSystemRoles(): Collection
    {
        return $this->model->where('is_system_role', true)->get();
    }

    /**
     * Get custom roles for tenant
     */
    public function getCustomRoles(int $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_system_role', false)
            ->get();
    }

    /**
     * Get role permissions
     */
    public function getRolePermissions(int $roleId): Collection
    {
        $role = $this->findOrFail($roleId);

        return $role->permissions;
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(Role $role, Permission $permission): void
    {
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * Remove permission from role
     */
    public function removePermission(Role $role, Permission $permission): void
    {
        $role->permissions()->detach($permission->id);
    }

    /**
     * Sync role permissions
     */
    public function syncPermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
    }

    /**
     * Get role users
     */
    public function getRoleUsers(int $roleId): Collection
    {
        $role = $this->findOrFail($roleId);

        return $role->users;
    }
}
