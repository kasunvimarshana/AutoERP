<?php

namespace App\Modules\Tenant\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Tenant\Models\Tenant;

class TenantRepository extends BaseRepository implements TenantRepositoryInterface
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
     * Get active tenants
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTenants()
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * Get tenants on trial
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrialTenants()
    {
        return $this->model
            ->where('status', 'trial')
            ->where('trial_ends_at', '>', now())
            ->get();
    }

    /**
     * Get tenants with expiring subscriptions
     *
     * @param int $daysThreshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringSubscriptions(int $daysThreshold = 7)
    {
        return $this->model
            ->where('status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->whereBetween('subscription_ends_at', [
                now(),
                now()->addDays($daysThreshold)
            ])
            ->get();
    }
}
