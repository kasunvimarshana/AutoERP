<?php

namespace App\Modules\Procurement\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Procurement\Models\Supplier;

/**
 * Supplier Policy
 * 
 * Authorization policies for supplier management operations
 * 
 * @package App\Modules\Procurement\Policies
 */
class SupplierPolicy
{
    /**
     * Determine whether the user can view any suppliers
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('suppliers.view');
    }

    /**
     * Determine whether the user can view the supplier
     *
     * @param User $user
     * @param Supplier $supplier
     * @return bool
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.view') && $user->tenant_id === $supplier->tenant_id;
    }

    /**
     * Determine whether the user can create suppliers
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('suppliers.create');
    }

    /**
     * Determine whether the user can update the supplier
     *
     * @param User $user
     * @param Supplier $supplier
     * @return bool
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.update') && $user->tenant_id === $supplier->tenant_id;
    }

    /**
     * Determine whether the user can delete the supplier
     *
     * @param User $user
     * @param Supplier $supplier
     * @return bool
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete') && $user->tenant_id === $supplier->tenant_id;
    }

    /**
     * Determine whether the user can restore the supplier
     *
     * @param User $user
     * @param Supplier $supplier
     * @return bool
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete') && $user->tenant_id === $supplier->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the supplier
     *
     * @param User $user
     * @param Supplier $supplier
     * @return bool
     */
    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete') && $user->tenant_id === $supplier->tenant_id;
    }
}
