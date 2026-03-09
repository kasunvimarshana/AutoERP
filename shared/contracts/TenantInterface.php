<?php

declare(strict_types=1);

namespace Shared\Contracts;

/**
 * Tenant Interface
 * 
 * Contract for multi-tenant context management.
 */
interface TenantInterface
{
    /**
     * Get the current tenant ID.
     */
    public function getTenantId(): string|int;

    /**
     * Get the tenant database connection name.
     */
    public function getDatabaseConnection(): string;

    /**
     * Get a tenant-specific configuration value.
     */
    public function getConfig(string $key, mixed $default = null): mixed;

    /**
     * Set runtime tenant configuration.
     */
    public function setConfig(string $key, mixed $value): void;
}
