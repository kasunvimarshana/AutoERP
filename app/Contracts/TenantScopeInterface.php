<?php

namespace App\Contracts;

/**
 * Tenant Scope Interface
 *
 * Defines the contract for tenant-aware models and repositories.
 * Ensures proper tenant isolation across the application.
 */
interface TenantScopeInterface
{
    /**
     * Get the tenant identifier
     *
     * @return int|string|null
     */
    public function getTenantId();

    /**
     * Set the tenant identifier
     */
    public function setTenantId($tenantId): self;

    /**
     * Scope query to current tenant
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTenant($query);
}
