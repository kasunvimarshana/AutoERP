<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view') || $user->hasRole(['admin', 'manager', 'staff']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id
            && ($user->can('invoices.view') || $user->hasRole(['admin', 'manager', 'staff']));
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create') || $user->hasRole(['admin', 'manager']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id
            && ($user->can('invoices.update') || $user->hasRole(['admin', 'manager']));
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id
            && ($user->can('invoices.send') || $user->hasRole(['admin', 'manager']));
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id
            && ($user->can('invoices.void') || $user->hasRole(['admin']));
    }
}
