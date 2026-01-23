<?php

namespace App\Modules\AuthManagement\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any users
     */
    public function viewAny(User $authenticatedUser): bool
    {
        // Super admin can view all users
        if ($authenticatedUser->role === 'super_admin') {
            return true;
        }

        // Admin can view users in their tenant
        return $authenticatedUser->hasPermissionTo('users.view');
    }

    /**
     * Determine if the user can view a specific user
     */
    public function view(User $authenticatedUser, User $targetUser): bool
    {
        // Super admin can view any user
        if ($authenticatedUser->role === 'super_admin') {
            return true;
        }

        // Users can view themselves
        if ($authenticatedUser->id === $targetUser->id) {
            return true;
        }

        // Check permission and tenant access
        if (!$authenticatedUser->hasPermissionTo('users.view')) {
            return false;
        }

        // Must be in same tenant
        if ($authenticatedUser->tenant_id !== $targetUser->tenant_id) {
            return false;
        }

        // Check vendor/branch restrictions
        if ($authenticatedUser->vendor_id && $authenticatedUser->vendor_id !== $targetUser->vendor_id) {
            return false;
        }

        if ($authenticatedUser->branch_id && $authenticatedUser->branch_id !== $targetUser->branch_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can create users
     */
    public function create(User $authenticatedUser): bool
    {
        // Super admin can create users
        if ($authenticatedUser->role === 'super_admin') {
            return true;
        }

        return $authenticatedUser->hasPermissionTo('users.create');
    }

    /**
     * Determine if the user can update a specific user
     */
    public function update(User $authenticatedUser, User $targetUser): bool
    {
        // Super admin can update any user
        if ($authenticatedUser->role === 'super_admin') {
            return true;
        }

        // Users can update themselves (limited fields)
        if ($authenticatedUser->id === $targetUser->id) {
            return true;
        }

        // Check permission and tenant access
        if (!$authenticatedUser->hasPermissionTo('users.update')) {
            return false;
        }

        // Must be in same tenant
        if ($authenticatedUser->tenant_id !== $targetUser->tenant_id) {
            return false;
        }

        // Check vendor/branch restrictions
        if ($authenticatedUser->vendor_id && $authenticatedUser->vendor_id !== $targetUser->vendor_id) {
            return false;
        }

        if ($authenticatedUser->branch_id && $authenticatedUser->branch_id !== $targetUser->branch_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can delete a specific user
     */
    public function delete(User $authenticatedUser, User $targetUser): bool
    {
        // Super admin can delete any user
        if ($authenticatedUser->role === 'super_admin') {
            return true;
        }

        // Users cannot delete themselves
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        // Check permission and tenant access
        if (!$authenticatedUser->hasPermissionTo('users.delete')) {
            return false;
        }

        // Must be in same tenant
        if ($authenticatedUser->tenant_id !== $targetUser->tenant_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can manage roles for a specific user
     */
    public function manageRoles(User $authenticatedUser, User $targetUser): bool
    {
        // Super admin can manage roles for any user
        if ($authenticatedUser->role === 'super_admin') {
            return true;
        }

        // Users cannot manage their own roles
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        // Check permission and tenant access
        if (!$authenticatedUser->hasPermissionTo('users.manage-roles')) {
            return false;
        }

        // Must be in same tenant
        return $authenticatedUser->tenant_id === $targetUser->tenant_id;
    }

    /**
     * Determine if the user can impersonate another user
     */
    public function impersonate(User $authenticatedUser, User $targetUser): bool
    {
        // Only super admin can impersonate
        if ($authenticatedUser->role !== 'super_admin') {
            return false;
        }

        // Cannot impersonate yourself
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        return true;
    }
}
