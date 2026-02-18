<?php

namespace Modules\IAM\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IAM\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $model): bool
    {
        // Users can view their own profile or if they have permission
        return $user->id === $model->id || $user->can('user.view');
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $user, User $model): bool
    {
        // Users can update their own profile or if they have permission
        return $user->id === $model->id || $user->can('user.update');
    }

    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('user.delete');
    }

    public function assignRole(User $user): bool
    {
        return $user->can('user.assign-role');
    }
}
