<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use App\Policies\BasePolicy;
use Modules\Sales\Entities\Customer;

/**
 * Customer Policy
 *
 * Authorization policy for customer management.
 * Handles CRUD operations and custom abilities like credit limit approval.
 */
class CustomerPolicy extends BasePolicy
{
    /**
     * Permission prefix for customer operations.
     */
    protected string $permissionPrefix = 'customer';

    /**
     * Determine whether the user can approve credit limit for the customer.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approveCreditLimit($user, Customer $customer): bool
    {
        return $this->checkPermission($user, 'approve-credit-limit') &&
               $this->checkTenantIsolation($user, $customer) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can view customer financial data.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewFinancialData($user, Customer $customer): bool
    {
        return $this->checkPermission($user, 'view-financial-data') &&
               $this->checkTenantIsolation($user, $customer);
    }

    /**
     * Determine whether the user can update customer credit limit.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function updateCreditLimit($user, Customer $customer): bool
    {
        return $this->checkPermission($user, 'update-credit-limit') &&
               $this->checkTenantIsolation($user, $customer) &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager']));
    }

    /**
     * Determine whether the user can merge customers.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function mergeCustomers($user): bool
    {
        return $this->checkPermission($user, 'merge') &&
               $this->hasAnyRole($user, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can export customer data.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function export($user): bool
    {
        return $this->checkPermission($user, 'export');
    }

    /**
     * Determine whether the user can import customer data.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function import($user): bool
    {
        return $this->checkPermission($user, 'import') &&
               $this->hasAnyRole($user, ['admin', 'manager']);
    }
}
