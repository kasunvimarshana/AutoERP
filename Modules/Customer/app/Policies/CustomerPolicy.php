<?php

declare(strict_types=1);

namespace Modules\Customer\Policies;

use App\Core\Policies\BasePolicy;
use App\Models\User;
use Modules\Customer\Models\Customer;

/**
 * Customer Authorization Policy
 *
 * Tenant-aware authorization for customer management operations
 * Implements both RBAC and ABAC patterns
 */
class CustomerPolicy extends BasePolicy
{
    /**
     * Determine if user can view any customers
     */
    public function viewAny(User $user): bool
    {
        return $user->can('customer.list') || $user->can('customer.read');
    }

    /**
     * Determine if user can view specific customer
     */
    public function view(User $user, Customer $customer): bool
    {
        if ($user->can('customer.read')) {
            return $this->isSameTenant($user, $customer);
        }

        return false;
    }

    /**
     * Determine if user can create customers
     */
    public function create(User $user): bool
    {
        return $user->can('customer.create');
    }

    /**
     * Determine if user can update specific customer
     */
    public function update(User $user, Customer $customer): bool
    {
        if ($user->can('customer.update')) {
            return $this->isSameTenant($user, $customer);
        }

        return false;
    }

    /**
     * Determine if user can delete specific customer
     */
    public function delete(User $user, Customer $customer): bool
    {
        if ($user->can('customer.delete')) {
            return $this->isSameTenant($user, $customer);
        }

        return false;
    }

    /**
     * Determine if user can search customers
     */
    public function search(User $user): bool
    {
        return $user->can('customer.list') || $user->can('customer.read');
    }

    /**
     * Determine if user can view customer statistics
     */
    public function viewStatistics(User $user, Customer $customer): bool
    {
        if ($user->can('customer.read') || $user->can('customer.list')) {
            return $this->isSameTenant($user, $customer);
        }

        return false;
    }

    /**
     * Perform before authorization checks
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admins can do everything
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return null;
    }
}
