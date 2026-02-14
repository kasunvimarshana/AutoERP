<?php

namespace App\Modules\User\Policies;

use App\Models\User;
use App\Modules\User\Models\User as UserModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, UserModel $model): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    /**
     * Determine if the user can update the user.
     */
    public function update(User $user, UserModel $model): bool
    {
        return $user->hasPermissionTo('users.update');
    }

    /**
     * Determine if the user can delete the user.
     */
    public function delete(User $user, UserModel $model): bool
    {
        return $user->hasPermissionTo('users.delete');
    }

    /**
     * Determine if the user can restore the user.
     */
    public function restore(User $user, UserModel $model): bool
    {
        return $user->hasPermissionTo('users.restore');
    }
}
