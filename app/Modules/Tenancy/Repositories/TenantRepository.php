<?php

namespace App\Modules\Tenancy\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Tenancy\Models\Tenant;

/**
 * Tenant Repository
 *
 * Handles data access for tenant operations
 */
class TenantRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Tenant::class;
    }

    /**
     * Find tenant by subdomain
     */
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return $this->model->where('subdomain', $subdomain)->first();
    }

    /**
     * Find tenant by domain
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return $this->model->where('domain', $domain)->first();
    }

    /**
     * Get all active tenants
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTenants()
    {
        return $this->model->where('is_active', true)->get();
    }
}
