<?php

namespace App\Modules\AuthManagement\Policies;

use App\Models\User;
use App\Modules\TenantManagement\Models\Tenant;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any tenants
     */
    public function viewAny(User $user): bool
    {
        // Only super admin can view all tenants
        return $user->role === 'super_admin';
    }

    /**
     * Determine if the user can view a specific tenant
     */
    public function view(User $user, Tenant $tenant): bool
    {
        // Super admin can view any tenant
        if ($user->role === 'super_admin') {
            return true;
        }

        // Users can only view their own tenant
        return $user->tenant_id === $tenant->id;
    }

    /**
     * Determine if the user can create tenants
     */
    public function create(User $user): bool
    {
        // Only super admin can create tenants
        return $user->role === 'super_admin';
    }

    /**
     * Determine if the user can update a specific tenant
     */
    public function update(User $user, Tenant $tenant): bool
    {
        // Super admin can update any tenant
        if ($user->role === 'super_admin') {
            return true;
        }

        // Tenant admin can update their own tenant
        if ($user->tenant_id === $tenant->id && $user->hasPermissionTo('tenants.update')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete a specific tenant
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        // Only super admin can delete tenants
        return $user->role === 'super_admin';
    }

    /**
     * Determine if the user can manage subscription for a tenant
     */
    public function manageSubscription(User $user, Tenant $tenant): bool
    {
        // Super admin can manage any tenant's subscription
        if ($user->role === 'super_admin') {
            return true;
        }

        // Tenant owner can manage their subscription
        if ($user->tenant_id === $tenant->id && $user->hasPermissionTo('tenants.manage-subscription')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user belongs to a specific tenant
     */
    public function access(User $user, Tenant $tenant): bool
    {
        // Super admin can access any tenant
        if ($user->role === 'super_admin') {
            return true;
        }

        // Check if user belongs to the tenant
        return $user->tenant_id === $tenant->id;
    }
}
