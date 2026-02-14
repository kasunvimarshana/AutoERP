<?php

namespace App\Modules\Tenant\Policies;

use App\Models\User;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any tenants.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('tenants.view');
    }

    /**
     * Determine if the user can view the tenant.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.view');
    }

    /**
     * Determine if the user can create tenants.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tenants.create');
    }

    /**
     * Determine if the user can update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.update');
    }

    /**
     * Determine if the user can delete the tenant.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.delete');
    }

    /**
     * Determine if the user can restore the tenant.
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.restore');
    }
}
