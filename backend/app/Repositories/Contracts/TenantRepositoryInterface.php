<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tenant Repository Interface
 * 
 * Defines the contract for tenant data access operations.
 */
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find tenant by slug
     *
     * @param string $slug
     * @return Tenant|null
     */
    public function findBySlug(string $slug): ?Tenant;

    /**
     * Find tenant by domain
     *
     * @param string $domain
     * @return Tenant|null
     */
    public function findByDomain(string $domain): ?Tenant;

    /**
     * Find tenant by subdomain
     *
     * @param string $subdomain
     * @return Tenant|null
     */
    public function findBySubdomain(string $subdomain): ?Tenant;

    /**
     * Get active tenants
     *
     * @return Collection
     */
    public function getActiveTenants(): Collection;

    /**
     * Get tenants on trial
     *
     * @return Collection
     */
    public function getTenantsOnTrial(): Collection;

    /**
     * Get expired trials
     *
     * @return Collection
     */
    public function getExpiredTrials(): Collection;
}
