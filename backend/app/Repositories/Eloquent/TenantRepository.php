<?php

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant Repository
 * 
 * Handles all data access operations for Tenant model.
 */
class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    /**
     * Constructor
     *
     * @param Tenant $model
     */
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    /**
     * Find tenant by slug
     *
     * @param string $slug
     * @return Tenant|null
     */
    public function findBySlug(string $slug): ?Tenant
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Find tenant by domain
     *
     * @param string $domain
     * @return Tenant|null
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return $this->model->where('domain', $domain)->first();
    }

    /**
     * Find tenant by subdomain
     *
     * @param string $subdomain
     * @return Tenant|null
     */
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return $this->model->where('subdomain', $subdomain)->first();
    }

    /**
     * Get active tenants
     *
     * @return Collection
     */
    public function getActiveTenants(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * Get tenants on trial
     *
     * @return Collection
     */
    public function getTenantsOnTrial(): Collection
    {
        return $this->model
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->get();
    }

    /**
     * Get expired trials
     *
     * @return Collection
     */
    public function getExpiredTrials(): Collection
    {
        return $this->model
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get();
    }

    /**
     * Filter by status
     *
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    protected function filterStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Filter by search term (name, slug, domain, subdomain)
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    protected function filterSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('slug', 'like', "%{$search}%")
              ->orWhere('domain', 'like', "%{$search}%")
              ->orWhere('subdomain', 'like', "%{$search}%");
        });
    }
}
