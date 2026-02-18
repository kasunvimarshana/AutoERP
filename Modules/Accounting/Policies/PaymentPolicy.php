<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use App\Policies\BasePolicy;
use Modules\Accounting\Entities\Payment;

/**
 * Payment Policy
 *
 * Authorization policy for payment management.
 * Handles CRUD operations and custom abilities like processing and reconciling payments.
 */
class PaymentPolicy extends BasePolicy
{
    /**
     * Permission prefix for payment operations.
     */
    protected string $permissionPrefix = 'payment';

    /**
     * Determine whether the user can process the payment.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function process($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'process') &&
               $this->checkTenantIsolation($user, $payment) &&
               $payment->status === 'pending' &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can reconcile the payment.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reconcile($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'reconcile') &&
               $this->checkTenantIsolation($user, $payment) &&
               $payment->status === 'completed' &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can approve the payment.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $payment) &&
               $payment->status === 'pending' &&
               ! $this->isOwner($user, $payment) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can reject the payment.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reject($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'reject') &&
               $this->checkTenantIsolation($user, $payment) &&
               $payment->status === 'pending' &&
               ! $this->isOwner($user, $payment) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can cancel the payment.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function cancel($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'cancel') &&
               $this->checkTenantIsolation($user, $payment) &&
               ! in_array($payment->status, ['completed', 'cancelled', 'failed']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can refund the payment.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function refund($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'refund') &&
               $this->checkTenantIsolation($user, $payment) &&
               $payment->status === 'completed' &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can void the payment.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function void($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'void') &&
               $this->checkTenantIsolation($user, $payment) &&
               in_array($payment->status, ['pending', 'processing']) &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager']));
    }

    /**
     * Determine whether the user can view payment details.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewDetails($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'view-details') &&
               $this->checkTenantIsolation($user, $payment);
    }

    /**
     * Determine whether the user can allocate the payment to invoices.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function allocate($user, Payment $payment): bool
    {
        return $this->checkPermission($user, 'allocate') &&
               $this->checkTenantIsolation($user, $payment) &&
               $payment->status === 'completed' &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can delete the payment (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, Payment $payment): bool
    {
        return parent::delete($user, $payment) &&
               in_array($payment->status, ['draft', 'pending', 'failed']);
    }

    /**
     * Determine whether the user can update the payment (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, Payment $payment): bool
    {
        return parent::update($user, $payment) &&
               in_array($payment->status, ['draft', 'pending']);
    }
}
