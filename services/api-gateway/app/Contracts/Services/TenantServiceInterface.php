<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Domain\Tenant\Models\Tenant;

/**
 * Tenant Service Interface
 */
interface TenantServiceInterface
{
    /**
     * Register a new tenant.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Tenant;

    /**
     * Get the current tenant from context.
     */
    public function current(): ?Tenant;

    /**
     * Set the current tenant context.
     */
    public function setContext(Tenant $tenant): void;

    /**
     * Apply tenant-specific runtime configuration dynamically.
     */
    public function applyRuntimeConfig(Tenant $tenant): void;

    /**
     * Update tenant runtime configuration without restart.
     *
     * @param  array<string, mixed>  $config
     */
    public function updateConfig(string $tenantId, string $group, array $config): bool;
}
