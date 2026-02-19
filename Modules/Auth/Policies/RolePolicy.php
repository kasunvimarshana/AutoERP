<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;

/**
 * RolePolicy
 *
 * Authorization policy for role management
 * Only admins should be able to manage roles
 */
class RolePolicy
{
    /**
     * Determine if user can view any roles
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.view') || $user->hasRole('admin');
    }

    /**
     * Determine if user can view a specific role
     */
    public function view(User $user, Role $role): bool
    {
        // User must be in same tenant and have permission
        return $role->tenant_id === $user->tenant_id &&
               ($user->hasPermission('roles.view') || $user->hasRole('admin'));
    }

    /**
     * Determine if user can create roles
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('roles.create') || $user->hasRole('admin');
    }

    /**
     * Determine if user can update a role
     */
    public function update(User $user, Role $role): bool
    {
        // Cannot update system roles
        if ($role->is_system) {
            return false;
        }

        // User must be in same tenant and have permission
        return $role->tenant_id === $user->tenant_id &&
               ($user->hasPermission('roles.update') || $user->hasRole('admin'));
    }

    /**
     * Determine if user can delete a role
     */
    public function delete(User $user, Role $role): bool
    {
        // Cannot delete system roles
        if ($role->is_system) {
            return false;
        }

        // User must be in same tenant and have permission
        return $role->tenant_id === $user->tenant_id &&
               ($user->hasPermission('roles.delete') || $user->hasRole('admin'));
    }

    /**
     * Determine if user can attach/detach permissions
     */
    public function managePermissions(User $user, Role $role): bool
    {
        // Cannot modify system role permissions
        if ($role->is_system) {
            return false;
        }

        // User must be in same tenant and have permission
        return $role->tenant_id === $user->tenant_id &&
               ($user->hasPermission('roles.manage-permissions') || $user->hasRole('admin'));
    }
}
