<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Invoice\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-invoices');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        if (!$user->can('view-invoices')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $invoice->organization_id) {
            if ($user->organization_id !== $invoice->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $invoice->branch_id) {
            return $user->branch_id === $invoice->branch_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create-invoices');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        if (!$user->can('edit-invoices')) {
            return false;
        }

        if (!$invoice->isEditable()) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $invoice->organization_id) {
            if ($user->organization_id !== $invoice->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $invoice->branch_id) {
            return $user->branch_id === $invoice->branch_id;
        }

        return true;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        if (!$user->can('delete-invoices')) {
            return false;
        }

        if ($invoice->isPaid()) {
            return false;
        }

        return $user->hasRole('super-admin') || $user->hasRole('admin');
    }

    public function approve(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        return $user->can('approve-invoices');
    }
}
