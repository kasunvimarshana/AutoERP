<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Superadmin bypass: allows all actions.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Tenant admin can do everything within their tenant
        if ($user->hasRole('admin') && $this->isSameTenant($user, null)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo($this->module() . '.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasPermissionTo($this->module() . '.view')
            && $this->isSameTenant($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo($this->module() . '.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermissionTo($this->module() . '.update')
            && $this->isSameTenant($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermissionTo($this->module() . '.delete')
            && $this->isSameTenant($user, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->hasPermissionTo($this->module() . '.restore')
            && $this->isSameTenant($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->hasRole(['superadmin', 'admin'])
            && $this->isSameTenant($user, $model);
    }

    /**
     * Validate that the user and model belong to the same tenant.
     */
    protected function isSameTenant(User $user, ?Model $model): bool
    {
        if ($model === null) {
            return true;
        }

        if (!property_exists($model, 'tenant_id') && !isset($model->tenant_id)) {
            return true;
        }

        return $user->tenant_id === $model->tenant_id;
    }

    /**
     * Return the module name used for permission lookups.
     * Must be implemented by concrete policies.
     */
    abstract protected function module(): string;
}
