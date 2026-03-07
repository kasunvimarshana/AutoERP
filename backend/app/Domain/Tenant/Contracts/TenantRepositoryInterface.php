<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract for the Tenant repository.
 */
interface TenantRepositoryInterface
{
    public function all(array $filters = []): mixed;

    public function findOrFail(int|string $id): Model;

    public function find(int|string $id): ?Model;

    public function create(array $attributes): Model;

    public function update(int|string $id, array $attributes): Model;

    public function delete(int|string $id): bool;

    /** Find a tenant by its unique slug. */
    public function findBySlug(string $slug): ?Model;

    /** Find a tenant by domain name. */
    public function findByDomain(string $domain): ?Model;

    /**
     * Return all active tenants.
     */
    public function findActive(): Collection;

    /**
     * Update the runtime configuration key-value store for a tenant.
     */
    public function updateConfig(int|string $tenantId, array $config): Model;
}
