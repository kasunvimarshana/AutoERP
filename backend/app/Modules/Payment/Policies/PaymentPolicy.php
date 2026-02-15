<?php

declare(strict_types=1);

namespace App\Modules\Payment\Policies;

use App\Models\User;
use App\Modules\Payment\Models\Payment;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-payments');
    }

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $payment->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('view-payments')) {
            return false;
        }

        // Super admins can view all payments in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level check
        if ($user->organization_id && $payment->organization_id) {
            if ($user->organization_id !== $payment->organization_id) {
                return false;
            }
        }

        // Branch-level check
        if ($user->branch_id && $payment->branch_id) {
            return $user->branch_id === $payment->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(User $user): bool
    {
        return $user->can('create-payments');
    }

    /**
     * Determine whether the user can update the payment.
     */
    public function update(User $user, Payment $payment): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $payment->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('edit-payments')) {
            return false;
        }

        // Super admins can update all payments in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level check
        if ($user->organization_id && $payment->organization_id) {
            if ($user->organization_id !== $payment->organization_id) {
                return false;
            }
        }

        // Branch-level check
        if ($user->branch_id && $payment->branch_id) {
            return $user->branch_id === $payment->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the payment.
     */
    public function delete(User $user, Payment $payment): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $payment->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('delete-payments')) {
            return false;
        }

        // Cannot delete reconciled payments
        if ($payment->reconciliation_status === 'reconciled') {
            return false;
        }

        // Super admins can delete payments in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level check
        if ($user->organization_id && $payment->organization_id) {
            if ($user->organization_id !== $payment->organization_id) {
                return false;
            }
        }

        // Branch-level check
        if ($user->branch_id && $payment->branch_id) {
            return $user->branch_id === $payment->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can reconcile the payment.
     */
    public function reconcile(User $user, Payment $payment): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $payment->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('reconcile-payments')) {
            return false;
        }

        // Payment must be completed
        if ($payment->status !== 'completed') {
            return false;
        }

        // Super admins can reconcile payments
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return true;
    }
}
