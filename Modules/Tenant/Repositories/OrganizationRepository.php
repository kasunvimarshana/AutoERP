<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Repositories\BaseRepository;
use Modules\Tenant\Models\Organization;

/**
 * Organization Repository
 *
 * Handles data access operations for Organization model with
 * specialized methods for hierarchical tree management
 */
class OrganizationRepository extends BaseRepository
{
    /**
     * Make a new Organization model instance.
     */
    protected function makeModel(): Model
    {
        return new Organization;
    }

    /**
     * Get organizations for a specific tenant.
     */
    public function getForTenant(string $tenantId, bool $onlyActive = true): Collection
    {
        $query = $this->model->forTenant($tenantId);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get all root organizations (no parent) for a tenant.
     */
    public function getRootOrganizations(string $tenantId, bool $onlyActive = true): Collection
    {
        $query = $this->model->forTenant($tenantId)->whereNull('parent_id');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get children of a specific organization.
     */
    public function getChildren(string $organizationId, bool $onlyActive = true): Collection
    {
        $query = $this->model->where('parent_id', $organizationId);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get full organization tree for a tenant.
     */
    public function getTree(string $tenantId, bool $onlyActive = true): Collection
    {
        $query = $this->model->with(['children' => function ($q) use ($onlyActive) {
            if ($onlyActive) {
                $q->active();
            }
        }])
            ->forTenant($tenantId)
            ->whereNull('parent_id');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get organization with all ancestors.
     */
    public function getWithAncestors(string $organizationId): ?Model
    {
        $organization = $this->find($organizationId);

        if (! $organization) {
            return null;
        }

        $ancestors = collect();
        $current = $organization;

        while ($current->parent_id) {
            $parent = $this->find($current->parent_id);
            if ($parent) {
                $ancestors->prepend($parent);
                $current = $parent;
            } else {
                break;
            }
        }

        $organization->ancestors = $ancestors;

        return $organization;
    }

    /**
     * Get organization with all descendants.
     */
    public function getWithDescendants(string $organizationId, bool $onlyActive = true): ?Model
    {
        $organization = $this->model->with(['children' => function ($q) use ($onlyActive) {
            if ($onlyActive) {
                $q->active();
            }
        }])->find($organizationId);

        if (! $organization) {
            return null;
        }

        return $organization;
    }

    /**
     * Get all descendant IDs of an organization.
     */
    public function getDescendantIds(string $organizationId, bool $onlyActive = true): array
    {
        $organization = $this->find($organizationId);

        if (! $organization) {
            return [];
        }

        $descendants = [];
        $this->collectDescendantIds($organization, $descendants, $onlyActive);

        return $descendants;
    }

    /**
     * Recursively collect descendant IDs.
     */
    protected function collectDescendantIds(Model $organization, array &$descendants, bool $onlyActive): void
    {
        $query = $this->model->where('parent_id', $organization->id);

        if ($onlyActive) {
            $query->active();
        }

        $children = $query->get();

        foreach ($children as $child) {
            $descendants[] = $child->id;
            $this->collectDescendantIds($child, $descendants, $onlyActive);
        }
    }

    /**
     * Get organization hierarchy level.
     */
    public function getLevel(string $organizationId): int
    {
        $organization = $this->find($organizationId);

        if (! $organization) {
            return 0;
        }

        $level = 0;
        $current = $organization;

        while ($current->parent_id) {
            $level++;
            $parent = $this->find($current->parent_id);
            if ($parent) {
                $current = $parent;
            } else {
                break;
            }
        }

        return $level;
    }

    /**
     * Check if organization has children.
     */
    public function hasChildren(string $organizationId, bool $onlyActive = true): bool
    {
        $query = $this->model->where('parent_id', $organizationId);

        if ($onlyActive) {
            $query->active();
        }

        return $query->exists();
    }

    /**
     * Move organization to new parent.
     */
    public function moveToParent(string $organizationId, ?string $newParentId): bool
    {
        if ($newParentId && $this->isDescendantOf($organizationId, $newParentId)) {
            return false;
        }

        $organization = $this->findOrFail($organizationId);
        $organization->parent_id = $newParentId;

        if ($newParentId) {
            $parent = $this->find($newParentId);
            if ($parent) {
                $organization->level = $parent->level + 1;
            }
        } else {
            $organization->level = 0;
        }

        return $organization->save();
    }

    /**
     * Check if organization is descendant of another.
     */
    public function isDescendantOf(string $organizationId, string $ancestorId): bool
    {
        $organization = $this->find($organizationId);

        if (! $organization) {
            return false;
        }

        $current = $organization;

        while ($current->parent_id) {
            if ($current->parent_id === $ancestorId) {
                return true;
            }

            $parent = $this->find($current->parent_id);
            if ($parent) {
                $current = $parent;
            } else {
                break;
            }
        }

        return false;
    }

    /**
     * Get organization path (breadcrumb).
     */
    public function getPath(string $organizationId): array
    {
        $organization = $this->getWithAncestors($organizationId);

        if (! $organization) {
            return [];
        }

        $path = [];

        if (isset($organization->ancestors)) {
            foreach ($organization->ancestors as $ancestor) {
                $path[] = [
                    'id' => $ancestor->id,
                    'name' => $ancestor->name,
                    'code' => $ancestor->code,
                    'level' => $ancestor->level,
                ];
            }
        }

        $path[] = [
            'id' => $organization->id,
            'name' => $organization->name,
            'code' => $organization->code,
            'level' => $organization->level,
        ];

        return $path;
    }

    /**
     * Find organization by code within a tenant.
     */
    public function findByCode(string $tenantId, string $code): ?Model
    {
        return $this->model->forTenant($tenantId)
            ->where('code', $code)
            ->first();
    }

    /**
     * Get organizations by type.
     */
    public function getByType(string $tenantId, string $type, bool $onlyActive = true): Collection
    {
        $query = $this->model->forTenant($tenantId)->where('type', $type);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Search organizations within a tenant.
     */
    public function searchOrganizations(string $tenantId, string $searchTerm, bool $onlyActive = true, int $perPage = 15)
    {
        $query = $this->model->forTenant($tenantId)
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%");
            });

        if ($onlyActive) {
            $query->active();
        }

        return $query->with('parent')->paginate($perPage);
    }

    /**
     * Get organizations by level.
     */
    public function getByLevel(string $tenantId, int $level, bool $onlyActive = true): Collection
    {
        $query = $this->model->forTenant($tenantId)->where('level', $level);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Toggle organization active status.
     */
    public function toggleActive(string $id): bool
    {
        $organization = $this->findOrFail($id);
        $organization->is_active = ! $organization->is_active;

        return $organization->save();
    }

    /**
     * Update organization metadata.
     */
    public function updateMetadata(string $id, array $metadata): bool
    {
        $organization = $this->findOrFail($id);
        $organization->metadata = array_merge($organization->metadata ?? [], $metadata);

        return $organization->save();
    }

    /**
     * Get organization metadata value.
     */
    public function getMetadata(string $id, string $key, mixed $default = null): mixed
    {
        $organization = $this->findOrFail($id);

        return data_get($organization->metadata, $key, $default);
    }

    /**
     * Get all ancestor IDs for an organization.
     */
    public function getAncestorIds(string $organizationId): array
    {
        $organization = $this->find($organizationId);

        if (! $organization) {
            return [];
        }

        $ancestors = [];
        $current = $organization;

        while ($current->parent_id) {
            $ancestors[] = $current->parent_id;
            $parent = $this->find($current->parent_id);
            if ($parent) {
                $current = $parent;
            } else {
                break;
            }
        }

        return $ancestors;
    }

    /**
     * Check if code is available within a tenant.
     */
    public function isCodeAvailable(string $tenantId, string $code, ?string $excludeOrgId = null): bool
    {
        $query = $this->model->forTenant($tenantId)->where('code', $code);

        if ($excludeOrgId) {
            $query->where('id', '!=', $excludeOrgId);
        }

        return ! $query->exists();
    }

    /**
     * Bulk activate organizations.
     */
    public function bulkActivate(array $organizationIds): int
    {
        return $this->model->whereIn('id', $organizationIds)
            ->update(['is_active' => true]);
    }

    /**
     * Bulk deactivate organizations.
     */
    public function bulkDeactivate(array $organizationIds): int
    {
        return $this->model->whereIn('id', $organizationIds)
            ->update(['is_active' => false]);
    }

    /**
     * Find organizations with filters and pagination.
     */
    public function findWithFilters(string $tenantId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->forTenant($tenantId);

        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active'] === 'true');
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['parent_id'])) {
            $parentId = $filters['parent_id'];
            if ($parentId === 'null' || $parentId === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        $query->with(['parent', 'children']);

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }
}
