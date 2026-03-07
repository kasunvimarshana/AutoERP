<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    protected function module(): string
    {
        return 'users';
    }

    /**
     * A user can view/update their own profile regardless of permissions.
     */
    public function viewSelf(User $user, User $model): bool
    {
        return $user->id === $model->id || parent::view($user, $model);
    }

    public function updateSelf(User $user, User $model): bool
    {
        return $user->id === $model->id || parent::update($user, $model);
    }
}
