<?php

namespace App\Modules\Tenant\Repositories;

use App\Base\BaseRepository;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

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
     */
    public function findByDomain(string $domain): ?Model
    {
        return $this->model
            ->whereHas('domains', function ($query) use ($domain) {
                $query->where('domain', $domain);
            })
            ->first();
    }

    /**
     * Get active tenants
     */
    public function getActive(array $columns = ['*'], array $relations = [])
    {
        return $this->model
            ->active()
            ->with($relations)
            ->get($columns);
    }

    /**
     * Get suspended tenants
     */
    public function getSuspended(array $columns = ['*'], array $relations = [])
    {
        return $this->model
            ->suspended()
            ->with($relations)
            ->get($columns);
    }

    /**
     * Suspend tenant
     */
    public function suspend(string $id): bool
    {
        return $this->update($id, ['status' => 'suspended']);
    }

    /**
     * Activate tenant
     */
    public function activate(string $id): bool
    {
        return $this->update($id, ['status' => 'active']);
    }

    /**
     * Cancel tenant
     */
    public function cancel(string $id): bool
    {
        return $this->update($id, ['status' => 'cancelled']);
    }
}
