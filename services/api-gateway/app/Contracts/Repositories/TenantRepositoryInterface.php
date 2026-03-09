<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tenant Repository Interface
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
     * Update tenant configuration at runtime.
     *
     * @param  array<string, mixed>  $config
     */
    public function updateConfiguration(string $tenantId, array $config): bool;

    /**
     * Get tenant configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(string $tenantId, ?string $group = null): array;
}
