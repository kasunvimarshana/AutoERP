<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\Gate;

/**
 * HasPermissions Trait
 *
 * Provides RBAC functionality using native Laravel Gates and Policies.
 * No external packages required.
 *
 * Usage:
 * 1. Add trait to your User model: use HasPermissions;
 * 2. Define permissions in database or config
 * 3. Use Laravel's native authorization: $user->can('edit-post')
 *
 * This trait works with Laravel's built-in Gate facade and Policy system.
 * Define gates in AuthServiceProvider or use Policy classes.
 *
 * Example:
 * // In AuthServiceProvider
 * Gate::define('edit-post', function ($user, $post) {
 *     return $user->hasPermission('edit-post') && $user->id === $post->user_id;
 * });
 *
 * // In Controller
 * $this->authorize('edit-post', $post);
 */
trait HasPermissions
{
    /**
     * Get all permissions for this user from roles and direct permissions.
     *
     * @return array<string>
     */
    public function getPermissions(): array
    {
        $permissions = [];

        // Get permissions from roles
        foreach ($this->roles as $role) {
            $permissions = array_merge($permissions, $role->permissions ?? []);
        }

        // Get direct permissions
        if (isset($this->permissions) && is_array($this->permissions)) {
            $permissions = array_merge($permissions, $this->permissions);
        }

        return array_unique($permissions);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the roles relationship.
     * Override this in your User model to define the actual relationship.
     */
    public function roles()
    {
        return $this->belongsToMany(
            config('auth.models.role', 'App\Models\Role'),
            'user_roles',
            'user_id',
            'role_id'
        );
    }
}
