<?php

namespace App\Policies;

use App\Domain\Models\User;

class UserPolicy
{
    /**
     * Super-admins and tenant-admins can do anything.
     */
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id ||
               ($user->tenant_id === $model->tenant_id && $user->hasPermissionTo('users.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id ||
               ($user->tenant_id === $model->tenant_id && $user->hasPermissionTo('users.update'));
    }

    public function delete(User $user, User $model): bool
    {
        return $user->tenant_id === $model->tenant_id && $user->hasPermissionTo('users.delete');
    }
}
