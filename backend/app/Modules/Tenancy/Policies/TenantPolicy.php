<?php

namespace App\Modules\Tenancy\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Tenancy\Models\Tenant;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tenants.view');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->can('tenants.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tenants.create');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->can('tenants.update');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->can('tenants.delete');
    }
}
