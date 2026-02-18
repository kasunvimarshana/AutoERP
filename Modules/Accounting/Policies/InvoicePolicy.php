<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use App\Policies\BasePolicy;
use Modules\Accounting\Entities\Invoice;

/**
 * Invoice Policy
 *
 * Authorization policy for invoice management.
 * Handles CRUD operations and custom abilities like sending, marking paid, and voiding invoices.
 */
class InvoicePolicy extends BasePolicy
{
    /**
     * Permission prefix for invoice operations.
     */
    protected string $permissionPrefix = 'invoice';

    /**
     * Determine whether the user can send the invoice.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function send($user, Invoice $invoice): bool
    {
        return $this->checkPermission($user, 'send') &&
               $this->checkTenantIsolation($user, $invoice) &&
               in_array($invoice->status, ['draft', 'approved']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can mark the invoice as paid.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function markPaid($user, Invoice $invoice): bool
    {
        return $this->checkPermission($user, 'mark-paid') &&
               $this->checkTenantIsolation($user, $invoice) &&
               ! in_array($invoice->status, ['paid', 'void', 'cancelled']) &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can mark the invoice as void.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function markVoid($user, Invoice $invoice): bool
    {
        return $this->checkPermission($user, 'mark-void') &&
               $this->checkTenantIsolation($user, $invoice) &&
               $invoice->status !== 'void' &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can approve the invoice.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, Invoice $invoice): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $invoice) &&
               $invoice->status === 'draft' &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can cancel the invoice.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function cancel($user, Invoice $invoice): bool
    {
        return $this->checkPermission($user, 'cancel') &&
               $this->checkTenantIsolation($user, $invoice) &&
               ! in_array($invoice->status, ['paid', 'void', 'cancelled']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can apply payment to the invoice.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function applyPayment($user, Invoice $invoice): bool
    {
        return $this->checkPermission($user, 'apply-payment') &&
               $this->checkTenantIsolation($user, $invoice) &&
               in_array($invoice->status, ['sent', 'approved', 'overdue', 'partially_paid']) &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can send payment reminder for the invoice.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function sendReminder($user, Invoice $invoice): bool
    {
        return $this->checkPermission($user, 'send-reminder') &&
               $this->checkTenantIsolation($user, $invoice) &&
               in_array($invoice->status, ['sent', 'overdue', 'partially_paid']);
    }

    /**
     * Determine whether the user can create credit note from the invoice.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function createCreditNote($user, Invoice $invoice): bool
    {
        return $this->checkPermission($user, 'create-credit-note') &&
               $this->checkTenantIsolation($user, $invoice) &&
               in_array($invoice->status, ['sent', 'paid']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can delete the invoice (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, Invoice $invoice): bool
    {
        return parent::delete($user, $invoice) &&
               $invoice->status === 'draft';
    }

    /**
     * Determine whether the user can update the invoice (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, Invoice $invoice): bool
    {
        return parent::update($user, $invoice) &&
               in_array($invoice->status, ['draft', 'approved']);
    }
}
