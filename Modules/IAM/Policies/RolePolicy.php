<?php

declare(strict_types=1);

namespace Modules\IAM\Policies;

use App\Policies\BasePolicy;
use Modules\IAM\Entities\Role;

/**
 * Role Policy
 *
 * Authorization policy for role management.
 * Handles CRUD operations and custom abilities for role administration.
 */
class RolePolicy extends BasePolicy
{
    /**
     * Permission prefix for role operations.
     */
    protected string $permissionPrefix = 'role';

    /**
     * Determine whether the user can assign the role to users.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function assign($user, Role $role): bool
    {
        return $this->checkPermission($user, 'assign') &&
               $this->checkTenantIsolation($user, $role) &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can revoke the role from users.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function revoke($user, Role $role): bool
    {
        return $this->checkPermission($user, 'revoke') &&
               $this->checkTenantIsolation($user, $role) &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can manage permissions for the role.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function managePermissions($user, Role $role): bool
    {
        return $this->checkPermission($user, 'manage-permissions') &&
               $this->checkTenantIsolation($user, $role) &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can clone the role.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function clone($user, Role $role): bool
    {
        return $this->checkPermission($user, 'create') &&
               $this->checkTenantIsolation($user, $role) &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can export roles.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function export($user): bool
    {
        return $this->checkPermission($user, 'export') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can import roles.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function import($user): bool
    {
        return $this->checkPermission($user, 'import') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }
}
