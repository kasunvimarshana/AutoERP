<?php

declare(strict_types=1);

namespace Modules\Tenancy\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Tenancy\Entities\Tenant;

/**
 * Tenant Repository Interface
 *
 * Defines the contract for tenant data access operations.
 * Extends the base repository interface with tenant-specific methods.
 */
interface TenantRepositoryInterface extends BaseRepositoryInterface
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
     * Get all active tenants.
     */
    public function getActiveTenants(): Collection;

    /**
     * Search tenants by name, slug, or domain.
     */
    public function search(string $query): Collection;
}
