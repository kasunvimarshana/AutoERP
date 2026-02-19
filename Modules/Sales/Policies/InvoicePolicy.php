<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Modules\Auth\Models\User;
use Modules\Sales\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.view')
            && $user->tenant_id === $invoice->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.update')
            && $user->tenant_id === $invoice->tenant_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.delete')
            && $user->tenant_id === $invoice->tenant_id;
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.delete')
            && $user->tenant_id === $invoice->tenant_id;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.force_delete')
            && $user->tenant_id === $invoice->tenant_id;
    }
}
