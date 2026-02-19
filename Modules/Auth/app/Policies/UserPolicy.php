<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * User Authorization Policy
 *
 * Tenant-aware authorization for user management operations
 * Implements both RBAC and ABAC patterns
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any users
     */
    public function viewAny(User $user): bool
    {
        // Check permission
        if ($user->can('user.list')) {
            return true;
        }

        // Super admin can view all
        return $user->hasRole('super-admin');
    }

    /**
     * Determine if user can view specific user
     */
    public function view(User $user, User $targetUser): bool
    {
        // Users can view their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Check permission
        if ($user->can('user.read')) {
            // Ensure same tenant in multi-tenant context
            return $this->isSameTenant($user, $targetUser);
        }

        return false;
    }

    /**
     * Determine if user can create users
     */
    public function create(User $user): bool
    {
        // Check permission
        return $user->can('user.create');
    }

    /**
     * Determine if user can update specific user
     */
    public function update(User $user, User $targetUser): bool
    {
        // Users can update their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Check permission and tenant
        if ($user->can('user.update')) {
            return $this->isSameTenant($user, $targetUser);
        }

        return false;
    }

    /**
     * Determine if user can delete specific user
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Users cannot delete themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Check permission and tenant
        if ($user->can('user.delete')) {
            return $this->isSameTenant($user, $targetUser);
        }

        return false;
    }

    /**
     * Determine if user can assign roles
     */
    public function assignRole(User $user, User $targetUser): bool
    {
        // Cannot modify own roles
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Check permission and tenant
        if ($user->can('role.assign')) {
            return $this->isSameTenant($user, $targetUser);
        }

        return false;
    }

    /**
     * Determine if user can revoke roles
     */
    public function revokeRole(User $user, User $targetUser): bool
    {
        // Cannot modify own roles
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Check permission and tenant
        if ($user->can('role.revoke')) {
            return $this->isSameTenant($user, $targetUser);
        }

        return false;
    }

    /**
     * Check if users belong to the same tenant
     */
    protected function isSameTenant(User $user, User $targetUser): bool
    {
        // If tenancy helper is not available, allow (single-tenant mode)
        if (! function_exists('tenancy')) {
            return true;
        }

        // If tenancy is not initialized, allow (non-tenant context)
        if (! tenancy()->initialized) {
            return true;
        }

        // Check if both users have tenant_id (if using column-based tenancy)
        if (isset($user->tenant_id) && isset($targetUser->tenant_id)) {
            return $user->tenant_id === $targetUser->tenant_id;
        }

        // For database-based tenancy, all users in current tenant context are same tenant
        return true;
    }

    /**
     * Perform before authorization checks
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admins can do everything
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }
}
