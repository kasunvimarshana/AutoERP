<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * BasePolicy
 * 
 * Base policy class with common authorization methods
 * All policy classes should extend this class
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Check if user belongs to the same tenant as the model
     */
    protected function isSameTenant(User $user, $model): bool
    {
        if (!method_exists($model, 'getAttribute')) {
            return false;
        }

        return $user->tenant_id === $model->tenant_id;
    }

    /**
     * Check if user has a specific permission
     */
    protected function hasPermission(User $user, string $permission): bool
    {
        return $user->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    /**
     * Check if user has a specific role
     */
    protected function hasRole(User $user, string $role): bool
    {
        return $user->roles()
            ->where('slug', $role)
            ->exists();
    }

    /**
     * Check if user is admin or super admin
     */
    protected function isAdmin(User $user): bool
    {
        return $this->hasRole($user, 'admin') || $this->hasRole($user, 'super-admin');
    }
}
