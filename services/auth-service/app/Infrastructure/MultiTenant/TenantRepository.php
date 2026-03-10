<?php

declare(strict_types=1);

namespace App\Infrastructure\MultiTenant;

use App\Infrastructure\Persistence\Models\Tenant;
use App\Infrastructure\Persistence\Repositories\BaseRepository;

/**
 * TenantRepository
 *
 * Data-access layer for Tenant entities.  Extends the fully-dynamic
 * BaseRepository so all filtering/pagination/search capabilities are
 * available out of the box.
 */
class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a tenant by its unique slug.
     *
     * @param  string $slug
     * @return Tenant|null
     */
    public function findBySlug(string $slug): ?Tenant
    {
        /** @var Tenant|null */
        return $this->findBy(['slug' => $slug]);
    }

    /**
     * Persist updated runtime configuration for a tenant.
     *
     * @param  string               $tenantId
     * @param  array<string, mixed> $config
     * @return void
     */
    public function updateConfig(string $tenantId, array $config): void
    {
        $this->model->newQuery()
            ->where('id', $tenantId)
            ->update(['config' => json_encode($config, JSON_THROW_ON_ERROR)]);
    }
}
