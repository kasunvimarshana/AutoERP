<?php

namespace App\Modules\Tenant\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Tenant\Models\Tenant;

/**
 * Tenant Repository
 *
 * Handles data access for tenant operations.
 */
class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    /**
     * Find tenant by slug.
     */
    public function findBySlug(string $slug): ?Tenant
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Find tenant by domain.
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return $this->model->where('domain', $domain)->first();
    }

    /**
     * Get active tenants with valid subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTenants()
    {
        return $this->model
            ->where('status', 'active')
            ->whereHas('activeSubscription')
            ->get();
    }
}
