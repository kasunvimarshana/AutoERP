<?php

declare(strict_types=1);

namespace Modules\IAM\Policies;

use App\Policies\BasePolicy;
use Modules\IAM\Entities\Permission;

/**
 * Permission Policy
 *
 * Authorization policy for permission management.
 * Handles CRUD operations and custom abilities for permission administration.
 */
class PermissionPolicy extends BasePolicy
{
    /**
     * Permission prefix for permission operations.
     */
    protected string $permissionPrefix = 'permission';

    /**
     * Determine whether the user can assign the permission to roles.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function assignToRole($user, Permission $permission): bool
    {
        return $this->checkPermission($user, 'assign-to-role') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can revoke the permission from roles.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function revokeFromRole($user, Permission $permission): bool
    {
        return $this->checkPermission($user, 'revoke-from-role') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can assign the permission directly to users.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function assignToUser($user, Permission $permission): bool
    {
        return $this->checkPermission($user, 'assign-to-user') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can revoke the permission from users.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function revokeFromUser($user, Permission $permission): bool
    {
        return $this->checkPermission($user, 'revoke-from-user') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can view permission usage.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewUsage($user, Permission $permission): bool
    {
        return $this->checkPermission($user, 'view-usage') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can export permissions.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function export($user): bool
    {
        return $this->checkPermission($user, 'export') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can import permissions.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function import($user): bool
    {
        return $this->checkPermission($user, 'import') &&
               ($this->hasRole($user, 'super-admin'));
    }

    /**
     * Override delete to prevent deletion of system permissions.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, Permission $permission): bool
    {
        // Prevent deletion of system permissions
        if ($permission->is_system ?? false) {
            return false;
        }

        return $this->checkPermission($user, 'delete') &&
               ($this->hasRole($user, 'super-admin'));
    }
}
