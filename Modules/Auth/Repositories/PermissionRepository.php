<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\Permission;
use Modules\Core\Repositories\BaseRepository;

/**
 * Permission Repository
 *
 * Handles permission data access operations
 */
class PermissionRepository extends BaseRepository
{
    /**
     * Make a new Permission model instance
     */
    protected function makeModel(): Model
    {
        return new Permission;
    }

    /**
     * Find permission by slug
     */
    public function findBySlug(string $slug, string $tenantId): ?Permission
    {
        return $this->model
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Find permission by name
     */
    public function findByName(string $name, string $tenantId): ?Permission
    {
        return $this->model
            ->where('name', $name)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Find permissions by tenant
     */
    public function findByTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->paginate($perPage);
    }

    /**
     * Find permissions by resource
     */
    public function findByResource(string $resource, string $tenantId): Collection
    {
        return $this->model
            ->where('resource', $resource)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    /**
     * Find permissions by action
     */
    public function findByAction(string $action, string $tenantId): Collection
    {
        return $this->model
            ->where('action', $action)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    /**
     * Find permission by resource and action
     */
    public function findByResourceAndAction(string $resource, string $action, string $tenantId): ?Permission
    {
        return $this->model
            ->where('resource', $resource)
            ->where('action', $action)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Get all system permissions
     */
    public function getSystemPermissions(string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_system', true)
            ->get();
    }

    /**
     * Get all custom permissions
     */
    public function getCustomPermissions(string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_system', false)
            ->get();
    }

    /**
     * Get permissions grouped by resource
     */
    public function getGroupedByResource(string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->orderBy('resource')
            ->orderBy('action')
            ->get()
            ->groupBy('resource');
    }

    /**
     * Get permissions with roles
     */
    public function findWithRoles(string $id): ?Permission
    {
        return $this->model
            ->with('roles')
            ->find($id);
    }

    /**
     * Get roles with permission
     */
    public function getRoles(string $permissionId): Collection
    {
        $permission = $this->findOrFail($permissionId);

        return $permission->roles;
    }

    /**
     * Count roles with permission
     */
    public function countRoles(string $permissionId): int
    {
        $permission = $this->findOrFail($permissionId);

        return $permission->roles()->count();
    }

    /**
     * Get users with permission
     */
    public function getUsers(string $permissionId): Collection
    {
        $permission = $this->findOrFail($permissionId);

        return $permission->users;
    }

    /**
     * Count users with permission
     */
    public function countUsers(string $permissionId): int
    {
        $permission = $this->findOrFail($permissionId);

        return $permission->users()->count();
    }

    /**
     * Search permissions
     */
    public function searchPermissions(string $term, string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('resource', 'like', "%{$term}%")
                    ->orWhere('action', 'like', "%{$term}%");
            })
            ->paginate($perPage);
    }

    /**
     * Check if slug exists for tenant
     */
    public function slugExists(string $slug, string $tenantId, ?string $excludePermissionId = null): bool
    {
        $query = $this->model
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId);

        if ($excludePermissionId) {
            $query->where('id', '!=', $excludePermissionId);
        }

        return $query->exists();
    }

    /**
     * Update permission metadata
     */
    public function updateMetadata(string $permissionId, array $metadata): bool
    {
        $permission = $this->findOrFail($permissionId);
        $currentMetadata = $permission->metadata ?? [];

        return $permission->update(['metadata' => array_merge($currentMetadata, $metadata)]);
    }

    /**
     * Delete permission and detach relationships
     */
    public function deleteWithRelationships(string $permissionId): bool
    {
        $permission = $this->findOrFail($permissionId);

        $this->beginTransaction();
        try {
            $permission->roles()->detach();
            $permission->users()->detach();
            $result = $permission->delete();

            $this->commit();

            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Bulk create permissions for a resource
     */
    public function createForResource(string $resource, array $actions, string $tenantId): Collection
    {
        $permissions = collect();

        $this->beginTransaction();
        try {
            foreach ($actions as $action) {
                $name = "{$resource}.{$action}";
                $slug = str_replace('.', '-', $name);

                $permission = $this->create([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'slug' => $slug,
                    'resource' => $resource,
                    'action' => $action,
                    'is_system' => false,
                ]);

                $permissions->push($permission);
            }

            $this->commit();

            return $permissions;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get all unique resources
     */
    public function getAllResources(string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->select('resource')
            ->distinct()
            ->orderBy('resource')
            ->pluck('resource');
    }

    /**
     * Get all unique actions
     */
    public function getAllActions(string $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    /**
     * Find permissions with filters and pagination.
     */
    public function findWithFilters(array $filters, ?string $tenantId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['is_system'])) {
            $query->where('is_system', filter_var($filters['is_system'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['resource'])) {
            $query->where('resource', $filters['resource']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('resource', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }
}

