<?php

declare(strict_types=1);

namespace Modules\CRM\Policies;

use Modules\Auth\Models\User;
use Modules\CRM\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermission('customers.view')
            && $user->tenant_id === $customer->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermission('customers.update')
            && $user->tenant_id === $customer->tenant_id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermission('customers.delete')
            && $user->tenant_id === $customer->tenant_id;
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->hasPermission('customers.delete')
            && $user->tenant_id === $customer->tenant_id;
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->hasPermission('customers.force_delete')
            && $user->tenant_id === $customer->tenant_id;
    }
}
