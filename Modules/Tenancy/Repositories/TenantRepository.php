<?php

declare(strict_types=1);

namespace Modules\Tenancy\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Tenancy\Entities\Tenant;
use Modules\Tenancy\Repositories\Contracts\TenantRepositoryInterface;

/**
 * Tenant Repository Implementation
 *
 * Provides data access operations for tenant entities.
 * Implements the repository pattern to abstract database operations.
 */
class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    /**
     * TenantRepository constructor.
     */
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): ?Tenant
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return $this->model->where('domain', $domain)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveTenants(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('slug', 'LIKE', "%{$query}%")
            ->orWhere('domain', 'LIKE', "%{$query}%")
            ->get();
    }
}
