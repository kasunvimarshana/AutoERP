<?php

namespace App\Modules\Role\Policies;

use App\Models\User;
use App\Modules\Role\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.delete');
    }
}
