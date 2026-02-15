<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\CRM\Models\Customer;

/**
 * Customer authorization policy
 * 
 * Enforces fine-grained RBAC/ABAC with strict tenant isolation.
 */
class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-customers');
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }

        if (!$user->can('view-customers')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level check
        if ($user->organization_id && $customer->organization_id) {
            if ($user->organization_id !== $customer->organization_id) {
                return false;
            }
        }

        // Branch-level check
        if ($user->branch_id && $customer->branch_id) {
            return $user->branch_id === $customer->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->can('create-customers');
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }

        if (!$user->can('edit-customers')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $customer->organization_id) {
            if ($user->organization_id !== $customer->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $customer->branch_id) {
            return $user->branch_id === $customer->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }

        if (!$user->can('delete-customers')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $customer->organization_id) {
            if ($user->organization_id !== $customer->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $customer->branch_id) {
            return $user->branch_id === $customer->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the customer.
     */
    public function restore(User $user, Customer $customer): bool
    {
        return $this->update($user, $customer);
    }

    /**
     * Determine whether the user can permanently delete the customer.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }

        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        return $user->can('delete-customers');
    }
}
