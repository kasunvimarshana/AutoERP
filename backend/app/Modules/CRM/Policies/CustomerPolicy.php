<?php

namespace App\Modules\CRM\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\CRM\Models\Customer;

/**
 * Customer Policy
 * 
 * Authorization policies for customer management operations
 * 
 * @package App\Modules\CRM\Policies
 */
class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    /**
     * Determine whether the user can view the customer
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view') && $user->tenant_id === $customer->tenant_id;
    }

    /**
     * Determine whether the user can create customers
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    /**
     * Determine whether the user can update the customer
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.update') && $user->tenant_id === $customer->tenant_id;
    }

    /**
     * Determine whether the user can delete the customer
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete') && $user->tenant_id === $customer->tenant_id;
    }

    /**
     * Determine whether the user can restore the customer
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function restore(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete') && $user->tenant_id === $customer->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the customer
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete') && $user->tenant_id === $customer->tenant_id;
    }
}
