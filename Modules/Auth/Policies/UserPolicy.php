<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use Modules\Auth\Models\User;

/**
 * UserPolicy
 *
 * Authorization policy for user management
 */
class UserPolicy
{
    /**
     * Determine if user can view any users
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view') || $user->hasRole('admin');
    }

    /**
     * Determine if user can view a specific user
     */
    public function view(User $currentUser, User $targetUser): bool
    {
        // Users can always view themselves
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Must be in same tenant
        if ($currentUser->tenant_id !== $targetUser->tenant_id) {
            return false;
        }

        return $currentUser->hasPermission('users.view') || $currentUser->hasRole('admin');
    }

    /**
     * Determine if user can create users
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('users.create') || $user->hasRole('admin');
    }

    /**
     * Determine if user can update a user
     */
    public function update(User $currentUser, User $targetUser): bool
    {
        // Users can always update themselves (with limitations)
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Must be in same tenant
        if ($currentUser->tenant_id !== $targetUser->tenant_id) {
            return false;
        }

        return $currentUser->hasPermission('users.update') || $currentUser->hasRole('admin');
    }

    /**
     * Determine if user can delete a user
     */
    public function delete(User $currentUser, User $targetUser): bool
    {
        // Cannot delete yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Must be in same tenant
        if ($currentUser->tenant_id !== $targetUser->tenant_id) {
            return false;
        }

        return $currentUser->hasPermission('users.delete') || $currentUser->hasRole('admin');
    }

    /**
     * Determine if user can assign roles
     */
    public function assignRoles(User $currentUser, User $targetUser): bool
    {
        // Cannot assign roles to yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Must be in same tenant
        if ($currentUser->tenant_id !== $targetUser->tenant_id) {
            return false;
        }

        return $currentUser->hasPermission('users.assign-roles') || $currentUser->hasRole('admin');
    }
}
