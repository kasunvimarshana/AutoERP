<?php

namespace App\Modules\Payment\Policies;

use App\Models\User;
use App\Modules\Payment\Models\Payment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('payments.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.update');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.delete');
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.restore');
    }

    public function complete(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.complete');
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.refund');
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.cancel');
    }
}
