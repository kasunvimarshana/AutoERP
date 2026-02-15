<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Tenant\Models\Tenant;

/**
 * Tenant authorization policy
 * 
 * Enforces strict access control for tenant management.
 * Only super admins can manage tenants.
 */
class TenantPolicy
{
    /**
     * Determine whether the user can view any tenants.
     * 
     * Regular users can only view their own tenant.
     * Super admins can view all tenants (for system administration).
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can view tenants
     */
    public function viewAny(User $user): bool
    {
        // Super admins can view all tenants
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Regular users can view tenant list (but will only see their own)
        return $user->can('view-tenants');
    }

    /**
     * Determine whether the user can view the tenant.
     * 
     * Users can only view their own tenant unless they are super admins.
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to view
     * @return bool True if the user can view this tenant
     */
    public function view(User $user, Tenant $tenant): bool
    {
        // Super admins can view any tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Users can only view their own tenant
        if ($user->tenant_id !== $tenant->id) {
            return false;
        }

        return $user->can('view-tenants');
    }

    /**
     * Determine whether the user can create tenants.
     * 
     * Only system-level super admins can create new tenants.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can create tenants
     */
    public function create(User $user): bool
    {
        // Only super admins can create tenants
        if (!$user->hasRole('super-admin')) {
            return false;
        }

        return $user->can('create-tenants');
    }

    /**
     * Determine whether the user can update the tenant.
     * 
     * Super admins can update any tenant.
     * Tenant admins can update their own tenant settings.
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to update
     * @return bool True if the user can update this tenant
     */
    public function update(User $user, Tenant $tenant): bool
    {
        // Super admins can update any tenant
        if ($user->hasRole('super-admin')) {
            return $user->can('edit-tenants');
        }

        // Tenant admins can update their own tenant
        if ($user->tenant_id !== $tenant->id) {
            return false;
        }

        // Must be admin with tenant edit permission
        return $user->hasRole('admin') && $user->can('edit-tenants');
    }

    /**
     * Determine whether the user can delete the tenant.
     * 
     * Only super admins can delete tenants (soft delete).
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to delete
     * @return bool True if the user can delete this tenant
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        // Cannot delete your own tenant
        if ($user->tenant_id === $tenant->id) {
            return false;
        }

        // Only super admins can delete tenants
        if (!$user->hasRole('super-admin')) {
            return false;
        }

        return $user->can('delete-tenants');
    }

    /**
     * Determine whether the user can restore the tenant.
     * 
     * Only super admins can restore deleted tenants.
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to restore
     * @return bool True if the user can restore this tenant
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        // Only super admins can restore tenants
        if (!$user->hasRole('super-admin')) {
            return false;
        }

        return $user->can('edit-tenants');
    }

    /**
     * Determine whether the user can permanently delete the tenant.
     * 
     * Only super admins can permanently delete tenants.
     * This is a critical operation that should be used with extreme caution.
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to permanently delete
     * @return bool True if the user can permanently delete this tenant
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        // Cannot delete your own tenant
        if ($user->tenant_id === $tenant->id) {
            return false;
        }

        // Only super admins can force delete tenants
        if (!$user->hasRole('super-admin')) {
            return false;
        }

        return $user->can('delete-tenants');
    }

    /**
     * Determine whether the user can manage tenant subscriptions.
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to manage subscription for
     * @return bool True if the user can manage subscriptions
     */
    public function manageSubscription(User $user, Tenant $tenant): bool
    {
        // Super admins can manage any tenant's subscription
        if ($user->hasRole('super-admin')) {
            return $user->can('manage-subscriptions');
        }

        // Tenant admins can manage their own subscription
        if ($user->tenant_id !== $tenant->id) {
            return false;
        }

        return $user->hasRole('admin') && $user->can('manage-subscriptions');
    }

    /**
     * Determine whether the user can view tenant settings.
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to view settings for
     * @return bool True if the user can view settings
     */
    public function viewSettings(User $user, Tenant $tenant): bool
    {
        // Super admins can view any tenant's settings
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Users can only view their own tenant's settings
        if ($user->tenant_id !== $tenant->id) {
            return false;
        }

        return $user->can('view-tenant-settings');
    }

    /**
     * Determine whether the user can update tenant settings.
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to update settings for
     * @return bool True if the user can update settings
     */
    public function updateSettings(User $user, Tenant $tenant): bool
    {
        // Super admins can update any tenant's settings
        if ($user->hasRole('super-admin')) {
            return $user->can('edit-tenant-settings');
        }

        // Users can only update their own tenant's settings
        if ($user->tenant_id !== $tenant->id) {
            return false;
        }

        return $user->hasRole('admin') && $user->can('edit-tenant-settings');
    }
}
