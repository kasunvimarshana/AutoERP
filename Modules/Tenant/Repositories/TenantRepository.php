<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Repositories\BaseRepository;
use Modules\Tenant\Models\Tenant;

/**
 * Tenant Repository
 *
 * Handles data access operations for Tenant model with
 * specialized methods for domain/subdomain lookup
 */
class TenantRepository extends BaseRepository
{
    /**
     * Make a new Tenant model instance.
     */
    protected function makeModel(): Model
    {
        return new Tenant;
    }

    /**
     * Find tenant by domain.
     */
    public function findByDomain(string $domain): ?Model
    {
        return $this->model->where('domain', $domain)->first();
    }

    /**
     * Find tenant by subdomain.
     */
    public function findBySubdomain(string $subdomain): ?Model
    {
        return $this->model->where('slug', $subdomain)->first();
    }

    /**
     * Find active tenant by domain.
     */
    public function findActiveTenantByDomain(string $domain): ?Model
    {
        return $this->model->active()
            ->where('domain', $domain)
            ->first();
    }

    /**
     * Find active tenant by subdomain.
     */
    public function findActiveTenantBySubdomain(string $subdomain): ?Model
    {
        return $this->model->active()
            ->where('slug', $subdomain)
            ->first();
    }

    /**
     * Get all active tenants.
     */
    public function getActiveTenants(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get tenants with organization counts.
     */
    public function getWithOrganizationCounts(bool $onlyActive = true): Collection
    {
        $query = $this->model->withCount('organizations');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Check if domain is available.
     */
    public function isDomainAvailable(string $domain, ?string $excludeTenantId = null): bool
    {
        $query = $this->model->where('domain', $domain);

        if ($excludeTenantId) {
            $query->where('id', '!=', $excludeTenantId);
        }

        return ! $query->exists();
    }

    /**
     * Check if slug is available.
     */
    public function isSlugAvailable(string $slug, ?string $excludeTenantId = null): bool
    {
        $query = $this->model->where('slug', $slug);

        if ($excludeTenantId) {
            $query->where('id', '!=', $excludeTenantId);
        }

        return ! $query->exists();
    }

    /**
     * Toggle tenant active status.
     */
    public function toggleActive(string $id): bool
    {
        $tenant = $this->findOrFail($id);
        $tenant->is_active = ! $tenant->is_active;

        return $tenant->save();
    }

    /**
     * Update tenant settings.
     */
    public function updateSettings(string $id, array $settings): bool
    {
        $tenant = $this->findOrFail($id);
        $tenant->settings = array_merge($tenant->settings ?? [], $settings);

        return $tenant->save();
    }

    /**
     * Get tenant setting value.
     */
    public function getSetting(string $id, string $key, mixed $default = null): mixed
    {
        $tenant = $this->findOrFail($id);

        return data_get($tenant->settings, $key, $default);
    }

    /**
     * Search tenants.
     */
    public function searchTenants(string $searchTerm, bool $onlyActive = true, int $perPage = 15)
    {
        $query = $this->model->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('slug', 'like', "%{$searchTerm}%")
                ->orWhere('domain', 'like', "%{$searchTerm}%");
        });

        if ($onlyActive) {
            $query->active();
        }

        return $query->paginate($perPage);
    }

    /**
     * Get tenant with organizations.
     */
    public function getWithOrganizations(string $id): ?Model
    {
        return $this->model->with('organizations')->find($id);
    }

    /**
     * Bulk activate tenants.
     */
    public function bulkActivate(array $tenantIds): int
    {
        return $this->model->whereIn('id', $tenantIds)
            ->update(['is_active' => true]);
    }

    /**
     * Bulk deactivate tenants.
     */
    public function bulkDeactivate(array $tenantIds): int
    {
        return $this->model->whereIn('id', $tenantIds)
            ->update(['is_active' => false]);
    }

    /**
     * Get recently created tenants.
     */
    public function getRecentTenants(int $limit = 10, bool $onlyActive = false): Collection
    {
        $query = $this->model->latest();

        if ($onlyActive) {
            $query->active();
        }

        return $query->limit($limit)->get();
    }

    /**
     * Find tenant with trashed records.
     */
    public function findWithTrashed(string $id): ?Model
    {
        return $this->model->withTrashed()->find($id);
    }

    /**
     * Restore a soft-deleted tenant.
     */
    public function restore(string $id): bool
    {
        $tenant = $this->findWithTrashed($id);

        if (! $tenant) {
            return false;
        }

        return $tenant->restore();
    }

    /**
     * Count organizations for a tenant.
     */
    public function countOrganizations(string $id): int
    {
        $tenant = $this->find($id);

        if (! $tenant) {
            return 0;
        }

        return $tenant->organizations()->count();
    }

    /**
     * Find tenants with filters and pagination.
     */
    public function findWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active'] === 'true');
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('slug', 'like', "%{$filters['search']}%")
                    ->orWhere('domain', 'like', "%{$filters['search']}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }
}
