<?php

namespace Modules\IAM\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\User;

class PermissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('permission.view');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('permission.view');
    }

    public function create(User $user): bool
    {
        return $user->can('permission.create');
    }

    public function delete(User $user, Permission $permission): bool
    {
        // Cannot delete system permissions
        if ($permission->is_system) {
            return false;
        }

        return $user->can('permission.delete');
    }
}
