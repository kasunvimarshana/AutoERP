<?php

declare(strict_types=1);

namespace Modules\Billing\Policies;

use Modules\Auth\Models\User;
use Modules\Billing\Models\SubscriptionPayment;

class SubscriptionPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('billing.payments.view');
    }

    public function view(User $user, SubscriptionPayment $payment): bool
    {
        return $user->hasPermission('billing.payments.view')
            && $user->tenant_id === $payment->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('billing.payments.create');
    }

    public function update(User $user, SubscriptionPayment $payment): bool
    {
        return $user->hasPermission('billing.payments.update')
            && $user->tenant_id === $payment->tenant_id;
    }

    public function delete(User $user, SubscriptionPayment $payment): bool
    {
        return $user->hasPermission('billing.payments.delete')
            && $user->tenant_id === $payment->tenant_id;
    }
}
