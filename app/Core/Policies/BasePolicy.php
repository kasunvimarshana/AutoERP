<?php

declare(strict_types=1);

namespace App\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Base Policy
 *
 * Base class for all authorization policies
 * Provides common authorization logic
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Check if user is super admin
     */
    protected function isSuperAdmin(mixed $user): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(mixed $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Check if user owns the resource
     */
    protected function isOwner(mixed $user, mixed $model): bool
    {
        return isset($model->user_id) && $model->user_id === $user->id;
    }

    /**
     * Check if user is in the same tenant as the resource
     */
    protected function isSameTenant(mixed $user, mixed $model): bool
    {
        if (! isset($model->tenant_id) || ! isset($user->tenant_id)) {
            return true; // Skip check if tenant_id not present
        }

        return $model->tenant_id === $user->tenant_id;
    }
}
