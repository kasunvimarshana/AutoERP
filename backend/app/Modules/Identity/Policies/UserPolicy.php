<?php

namespace App\Modules\Identity\Policies;

use App\Modules\Identity\Models\User;

/**
 * User Policy
 * 
 * Authorization policies for user management operations
 * 
 * @package App\Modules\Identity\Policies
 */
class UserPolicy
{
    /**
     * Determine whether the user can view any users
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can view the model
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('users.view') || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    /**
     * Determine whether the user can update the model
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function update(User $user, User $model): bool
    {
        return $user->can('users.update') || $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }
        
        return $user->can('users.delete');
    }

    /**
     * Determine whether the user can restore the model
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('users.delete');
    }

    /**
     * Determine whether the user can permanently delete the model
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('users.delete');
    }
}
