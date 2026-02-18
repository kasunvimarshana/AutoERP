<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Policy
 *
 * Abstract base policy providing common authorization methods.
 * Includes tenant isolation checks and permission verification.
 * All module policies should extend this base.
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Permission prefix for the resource.
     * Override in child classes (e.g., 'customer', 'product', 'invoice').
     */
    protected string $permissionPrefix;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewAny($user): bool
    {
        return $this->checkPermission($user, 'view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function view($user, Model $model): bool
    {
        return $this->checkPermission($user, 'view') && $this->checkTenantIsolation($user, $model);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function create($user): bool
    {
        return $this->checkPermission($user, 'create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, Model $model): bool
    {
        return $this->checkPermission($user, 'update') && $this->checkTenantIsolation($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, Model $model): bool
    {
        return $this->checkPermission($user, 'delete') && $this->checkTenantIsolation($user, $model);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function restore($user, Model $model): bool
    {
        return $this->checkPermission($user, 'restore') && $this->checkTenantIsolation($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function forceDelete($user, Model $model): bool
    {
        return $this->checkPermission($user, 'force-delete') && $this->checkTenantIsolation($user, $model);
    }

    /**
     * Check if user has permission for the action.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function checkPermission($user, string $action): bool
    {
        if (! isset($this->permissionPrefix)) {
            throw new \RuntimeException('Permission prefix must be defined in child policy');
        }

        $permission = "{$this->permissionPrefix}.{$action}";

        // Super admin has all permissions
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }

    /**
     * Check tenant isolation - ensure user can only access their tenant's data.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function checkTenantIsolation($user, Model $model): bool
    {
        // Get current tenant
        $tenant = app('tenant');

        if (! $tenant) {
            return false;
        }

        // If model has tenant_id, check it matches
        if (isset($model->tenant_id)) {
            return $model->tenant_id === $tenant->id;
        }

        // If model has organization_id and user has organization_id, check it matches
        if (isset($model->organization_id) && isset($user->organization_id)) {
            return $model->organization_id === $user->organization_id;
        }

        // For models without tenant_id, assume they belong to current tenant
        // This should be overridden in child policies if different logic is needed
        return true;
    }

    /**
     * Check if user has a specific role.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function hasRole($user, string|array $roles): bool
    {
        return $user->hasRole($roles);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function hasAnyRole($user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user has all of the given roles.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function hasAllRoles($user, array $roles): bool
    {
        return $user->hasAllRoles($roles);
    }

    /**
     * Check if user owns the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function isOwner($user, Model $model): bool
    {
        if (isset($model->user_id)) {
            return $model->user_id === $user->id;
        }

        if (isset($model->created_by)) {
            return $model->created_by === $user->id;
        }

        return false;
    }

    /**
     * Check if user is in the same department as the model.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    protected function isSameDepartment($user, Model $model): bool
    {
        if (! isset($user->department_id) || ! isset($model->department_id)) {
            return false;
        }

        return $user->department_id === $model->department_id;
    }
}
