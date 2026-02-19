<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use Modules\Auth\Models\Permission;
use Modules\Auth\Models\User;

/**
 * PermissionPolicy
 *
 * Authorization policy for permission management
 * Only super admins should be able to manage permissions
 */
class PermissionPolicy
{
    /**
     * Determine if user can view any permissions
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('permissions.view') || $user->hasRole('admin');
    }

    /**
     * Determine if user can view a specific permission
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->hasPermission('permissions.view') || $user->hasRole('admin');
    }

    /**
     * Determine if user can create permissions
     */
    public function create(User $user): bool
    {
        // Only super admins can create permissions
        return $user->hasRole('super-admin') || $user->hasPermission('permissions.create');
    }

    /**
     * Determine if user can update a permission
     */
    public function update(User $user, Permission $permission): bool
    {
        // Only super admins can update permissions
        return $user->hasRole('super-admin') || $user->hasPermission('permissions.update');
    }

    /**
     * Determine if user can delete a permission
     */
    public function delete(User $user, Permission $permission): bool
    {
        // Only super admins can delete permissions
        return $user->hasRole('super-admin') || $user->hasPermission('permissions.delete');
    }
}
