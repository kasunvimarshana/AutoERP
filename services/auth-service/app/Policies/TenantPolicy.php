<?php

namespace App\Policies;

use App\Domain\Models\Tenant;
use App\Domain\Models\User;

class TenantPolicy
{
    /**
     * Super-admins can do anything.
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
        return $user->hasPermissionTo('tenants.view');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->tenant_id === $tenant->id || $user->hasPermissionTo('tenants.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tenants.create');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.update');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.delete');
    }
}
