<?php

namespace App\Modules\Tenant\Repositories;

use App\Modules\Tenant\Models\Tenant;

interface TenantRepositoryInterface
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
     * Get active tenants
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTenants();

    /**
     * Get tenants on trial
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrialTenants();

    /**
     * Get tenants with expiring subscriptions
     *
     * @param int $daysThreshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringSubscriptions(int $daysThreshold = 7);
}
