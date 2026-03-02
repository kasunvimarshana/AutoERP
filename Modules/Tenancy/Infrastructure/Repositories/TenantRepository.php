<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Tenancy\Domain\Contracts\TenantRepositoryContract;
use Modules\Tenancy\Domain\Entities\Tenant;

/**
 * Tenant repository implementation.
 *
 * NOTE: The Tenant entity itself does NOT use the HasTenant global scope
 * because tenants are system-level records, not tenant-scoped records.
 */
class TenantRepository extends AbstractRepository implements TenantRepositoryContract
{
    protected string $modelClass = Tenant::class;

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): ?Tenant
    {
        /** @var Tenant|null */
        return $this->query()->where('slug', $slug)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByDomain(string $domain): ?Tenant
    {
        /** @var Tenant|null */
        return $this->query()->where('domain', $domain)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function allActive(): Collection
    {
        return $this->query()->where('is_active', true)->get();
    }
}
