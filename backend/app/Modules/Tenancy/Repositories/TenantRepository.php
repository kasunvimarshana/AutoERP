<?php

namespace App\Modules\Tenancy\Repositories;

use App\Core\BaseRepository;
use App\Modules\Tenancy\Models\Tenant;

/**
 * Tenant Repository
 * 
 * @package App\Modules\Tenancy\Repositories
 */
class TenantRepository extends BaseRepository
{
    /**
     * TenantRepository constructor.
     *
     * @param Tenant $model
     */
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    /**
     * Find tenant by domain
     *
     * @param string $domain
     * @return Tenant|null
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return $this->findBy('domain', $domain);
    }

    /**
     * Get active tenants
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive()
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * Get tenants with expired trials
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiredTrials()
    {
        return $this->model
            ->where('trial_ends_at', '<', now())
            ->where('status', 'trial')
            ->get();
    }
}
