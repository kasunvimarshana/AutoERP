<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Contracts;

use Modules\Core\Domain\Contracts\RepositoryContract;
use Modules\Tenancy\Domain\Entities\Tenant;

/**
 * Tenant repository contract.
 */
interface TenantRepositoryContract extends RepositoryContract
{
    /**
     * Find a tenant by its slug.
     */
    public function findBySlug(string $slug): ?Tenant;

    /**
     * Find a tenant by its domain.
     */
    public function findByDomain(string $domain): ?Tenant;

    /**
     * Return only active tenants.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Tenant>
     */
    public function allActive(): \Illuminate\Database\Eloquent\Collection;
}
