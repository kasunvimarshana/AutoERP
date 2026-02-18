<?php

declare(strict_types=1);

namespace Modules\IAM\Policies;

use App\Policies\BasePolicy;
use Modules\IAM\Entities\Group;

/**
 * Group Policy
 *
 * Authorization policy for group management.
 * Handles CRUD operations and custom abilities for user group administration.
 */
class GroupPolicy extends BasePolicy
{
    /**
     * Permission prefix for group operations.
     */
    protected string $permissionPrefix = 'group';

    /**
     * Determine whether the user can add members to the group.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function addMembers($user, Group $group): bool
    {
        return $this->checkPermission($user, 'add-members') &&
               $this->checkTenantIsolation($user, $group) &&
               ($this->hasAnyRole($user, ['admin', 'manager']));
    }

    /**
     * Determine whether the user can remove members from the group.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function removeMembers($user, Group $group): bool
    {
        return $this->checkPermission($user, 'remove-members') &&
               $this->checkTenantIsolation($user, $group) &&
               ($this->hasAnyRole($user, ['admin', 'manager']));
    }

    /**
     * Determine whether the user can view group members.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewMembers($user, Group $group): bool
    {
        return $this->checkPermission($user, 'view-members') &&
               $this->checkTenantIsolation($user, $group);
    }

    /**
     * Determine whether the user can manage group permissions.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function managePermissions($user, Group $group): bool
    {
        return $this->checkPermission($user, 'manage-permissions') &&
               $this->checkTenantIsolation($user, $group) &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can export groups.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function export($user): bool
    {
        return $this->checkPermission($user, 'export') &&
               ($this->hasAnyRole($user, ['admin']));
    }

    /**
     * Determine whether the user can import groups.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function import($user): bool
    {
        return $this->checkPermission($user, 'import') &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }
}
