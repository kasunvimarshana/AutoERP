<?php

declare(strict_types=1);

namespace Modules\Tenant\Policies;

use Modules\Auth\Models\User;
use Modules\Tenant\Models\Tenant;

/**
 * TenantPolicy
 *
 * Authorization policy for tenant operations
 */
class TenantPolicy
{
    /**
     * Determine whether the user can view any tenants
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tenants.view');
    }

    /**
     * Determine whether the user can view the tenant
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.view');
    }

    /**
     * Determine whether the user can create tenants
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('tenants.create');
    }

    /**
     * Determine whether the user can update the tenant
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.update');
    }

    /**
     * Determine whether the user can delete the tenant
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.delete');
    }

    /**
     * Determine whether the user can restore the tenant
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.restore');
    }
}
