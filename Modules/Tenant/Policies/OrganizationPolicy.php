<?php

declare(strict_types=1);

namespace Modules\Tenant\Policies;

use Modules\Auth\Models\User;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Services\TenantContext;

/**
 * OrganizationPolicy
 *
 * Authorization policy for organization operations
 */
class OrganizationPolicy
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Determine whether the user can view any organizations
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('organizations.view');
    }

    /**
     * Determine whether the user can view the organization
     */
    public function view(User $user, Organization $organization): bool
    {
        if (! $user->hasPermission('organizations.view')) {
            return false;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();

        return $organization->tenant_id === $tenantId;
    }

    /**
     * Determine whether the user can create organizations
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('organizations.create');
    }

    /**
     * Determine whether the user can update the organization
     */
    public function update(User $user, Organization $organization): bool
    {
        if (! $user->hasPermission('organizations.update')) {
            return false;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();

        return $organization->tenant_id === $tenantId;
    }

    /**
     * Determine whether the user can delete the organization
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (! $user->hasPermission('organizations.delete')) {
            return false;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();

        return $organization->tenant_id === $tenantId;
    }

    /**
     * Determine whether the user can restore the organization
     */
    public function restore(User $user, Organization $organization): bool
    {
        if (! $user->hasPermission('organizations.restore')) {
            return false;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();

        return $organization->tenant_id === $tenantId;
    }
}
