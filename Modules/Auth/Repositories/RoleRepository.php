<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\Role;
use Modules\Core\Repositories\BaseRepository;

/**
 * Role Repository
 *
 * Handles role data access operations
 */
class RoleRepository extends BaseRepository
{
    /**
     * Make a new Role model instance
     */
    protected function makeModel(): Model
    {
        return new Role;
    }

    /**
     * Find role by slug
     */
    public function findBySlug(string $slug, string $tenantId): ?Role
    {
        return $this->model
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Find role by name
     */
    public function findByName(string $name, string $tenantId): ?Role
    {
        return $this->model
            ->where('name', $name)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Find roles by tenant
     */
    public function findByTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->with('permissions')
            ->paginate($perPage);
    }

    /**
     * Get all system roles
     */
    public function getSystemRoles(string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_system', true)
            ->get();
    }

    /**
     * Get all custom roles
     */
    public function getCustomRoles(string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_system', false)
            ->get();
    }

    /**
     * Find role with permissions
     */
    public function findWithPermissions(string $id): ?Role
    {
        return $this->model
            ->with('permissions')
            ->find($id);
    }

    /**
     * Find role with users
     */
    public function findWithUsers(string $id): ?Role
    {
        return $this->model
            ->with('users')
            ->find($id);
    }

    /**
     * Attach permission to role
     */
    public function attachPermission(string $roleId, string $permissionId): void
    {
        $role = $this->findOrFail($roleId);
        if (! $role->permissions()->where('permission_id', $permissionId)->exists()) {
            $role->permissions()->attach($permissionId);
        }
    }

    /**
     * Detach permission from role
     */
    public function detachPermission(string $roleId, string $permissionId): void
    {
        $role = $this->findOrFail($roleId);
        $role->permissions()->detach($permissionId);
    }

    /**
     * Sync role permissions
     */
    public function syncPermissions(string $roleId, array $permissionIds): void
    {
        $role = $this->findOrFail($roleId);
        $role->permissions()->sync($permissionIds);
    }

    /**
     * Attach multiple permissions to role
     */
    public function attachPermissions(string $roleId, array $permissionIds): void
    {
        $role = $this->findOrFail($roleId);
        $existingIds = $role->permissions()->pluck('permission_id')->toArray();
        $newIds = array_diff($permissionIds, $existingIds);

        if (! empty($newIds)) {
            $role->permissions()->attach($newIds);
        }
    }

    /**
     * Detach multiple permissions from role
     */
    public function detachPermissions(string $roleId, array $permissionIds): void
    {
        $role = $this->findOrFail($roleId);
        $role->permissions()->detach($permissionIds);
    }

    /**
     * Get role permissions
     */
    public function getPermissions(string $roleId): Collection
    {
        $role = $this->findOrFail($roleId);

        return $role->permissions;
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $roleId, string $permissionName): bool
    {
        $role = $this->findOrFail($roleId);

        return $role->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Get users with role
     */
    public function getUsers(string $roleId, int $perPage = 15): LengthAwarePaginator
    {
        $role = $this->findOrFail($roleId);

        return $role->users()->paginate($perPage);
    }

    /**
     * Count users with role
     */
    public function countUsers(string $roleId): int
    {
        $role = $this->findOrFail($roleId);

        return $role->users()->count();
    }

    /**
     * Search roles by name
     */
    public function searchRoles(string $term, string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            })
            ->with('permissions')
            ->paginate($perPage);
    }

    /**
     * Check if slug exists for tenant
     */
    public function slugExists(string $slug, string $tenantId, ?string $excludeRoleId = null): bool
    {
        $query = $this->model
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId);

        if ($excludeRoleId) {
            $query->where('id', '!=', $excludeRoleId);
        }

        return $query->exists();
    }

    /**
     * Update role metadata
     */
    public function updateMetadata(string $roleId, array $metadata): bool
    {
        $role = $this->findOrFail($roleId);
        $currentMetadata = $role->metadata ?? [];

        return $role->update(['metadata' => array_merge($currentMetadata, $metadata)]);
    }

    /**
     * Delete role and detach relationships
     */
    public function deleteWithRelationships(string $roleId): bool
    {
        $role = $this->findOrFail($roleId);

        $this->beginTransaction();
        try {
            $role->permissions()->detach();
            $role->users()->detach();
            $result = $role->delete();

            $this->commit();

            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Copy permissions from one role to another
     */
    public function copyPermissions(string $sourceRoleId, string $targetRoleId): void
    {
        $sourceRole = $this->findOrFail($sourceRoleId);
        $targetRole = $this->findOrFail($targetRoleId);

        $permissionIds = $sourceRole->permissions()->pluck('permission_id')->toArray();
        $targetRole->permissions()->sync($permissionIds);
    }

    /**
     * Find roles with filters and pagination.
     */
    public function findWithFilters(array $filters, ?string $tenantId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['permissions']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['is_system'])) {
            $query->where('is_system', filter_var($filters['is_system'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
