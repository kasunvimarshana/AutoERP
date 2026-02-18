<?php

namespace Modules\IAM\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('role.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('role.view');
    }

    public function create(User $user): bool
    {
        return $user->can('role.create');
    }

    public function update(User $user, Role $role): bool
    {
        // Cannot update system roles
        if ($role->is_system) {
            return false;
        }

        return $user->can('role.update');
    }

    public function delete(User $user, Role $role): bool
    {
        // Cannot delete system roles
        if ($role->is_system) {
            return false;
        }

        return $user->can('role.delete');
    }

    public function assignPermission(User $user, Role $role): bool
    {
        // Cannot modify system role permissions
        if ($role->is_system) {
            return false;
        }

        return $user->can('role.assign-permission');
    }
}
