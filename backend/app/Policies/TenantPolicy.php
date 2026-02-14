<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;

/**
 * TenantPolicy
 * 
 * Authorization policy for Tenant model
 */
class TenantPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any tenants.
     */
    public function viewAny(User $user): bool
    {
        // Only super admins can view all tenants
        return $this->hasRole($user, 'super-admin');
    }

    /**
     * Determine whether the user can view the tenant.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->tenant_id === $tenant->id || $this->hasRole($user, 'super-admin');
    }

    /**
     * Determine whether the user can create tenants.
     */
    public function create(User $user): bool
    {
        // Only super admins can create tenants
        return $this->hasRole($user, 'super-admin');
    }

    /**
     * Determine whether the user can update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return ($user->tenant_id === $tenant->id && $this->isAdmin($user)) || 
               $this->hasRole($user, 'super-admin');
    }

    /**
     * Determine whether the user can delete the tenant.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        // Only super admins can delete tenants
        return $this->hasRole($user, 'super-admin');
    }
}
