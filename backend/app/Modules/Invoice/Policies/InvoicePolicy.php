<?php

namespace App\Modules\Invoice\Policies;

use App\Models\User;
use App\Modules\Invoice\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.update');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.delete');
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.restore');
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.send');
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.cancel');
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.record_payment');
    }

    public function generatePDF(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.generate_pdf');
    }
}
