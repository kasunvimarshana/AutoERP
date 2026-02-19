<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Tenant\Models\Organization;
use Modules\Tenant\Models\Tenant;

/**
 * TenantContext
 *
 * Manages the current tenant and organization context for the request
 */
class TenantContext
{
    protected ?string $currentTenantId = null;

    protected ?string $currentOrganizationId = null;

    protected ?Tenant $currentTenant = null;

    protected ?Organization $currentOrganization = null;

    /**
     * Set the current tenant
     */
    public function setTenant(string|Tenant $tenant): void
    {
        if ($tenant instanceof Tenant) {
            $this->currentTenant = $tenant;
            $this->currentTenantId = $tenant->id;
        } else {
            $this->currentTenantId = $tenant;
            $this->currentTenant = null;
        }
    }

    /**
     * Set the current organization
     */
    public function setOrganization(string|Organization $organization): void
    {
        if ($organization instanceof Organization) {
            $this->currentOrganization = $organization;
            $this->currentOrganizationId = $organization->id;

            // Also set the tenant from organization
            if ($organization->tenant_id) {
                $this->setTenant($organization->tenant_id);
            }
        } else {
            $this->currentOrganizationId = $organization;
            $this->currentOrganization = null;
        }
    }

    /**
     * Get the current tenant ID
     */
    public function getCurrentTenantId(): ?string
    {
        return $this->currentTenantId;
    }

    /**
     * Get the current tenant
     */
    public function getCurrentTenant(): ?Tenant
    {
        if (! $this->currentTenant && $this->currentTenantId) {
            $this->currentTenant = Tenant::find($this->currentTenantId);
        }

        return $this->currentTenant;
    }

    /**
     * Get the current organization ID
     */
    public function getCurrentOrganizationId(): ?string
    {
        return $this->currentOrganizationId;
    }

    /**
     * Get the current organization
     */
    public function getCurrentOrganization(): ?Organization
    {
        if (! $this->currentOrganization && $this->currentOrganizationId) {
            $this->currentOrganization = Organization::find($this->currentOrganizationId);
        }

        return $this->currentOrganization;
    }

    /**
     * Clear the current context
     */
    public function clear(): void
    {
        $this->currentTenantId = null;
        $this->currentOrganizationId = null;
        $this->currentTenant = null;
        $this->currentOrganization = null;
    }

    /**
     * Check if a tenant is set
     */
    public function hasTenant(): bool
    {
        return $this->currentTenantId !== null;
    }

    /**
     * Check if an organization is set
     */
    public function hasOrganization(): bool
    {
        return $this->currentOrganizationId !== null;
    }
}
