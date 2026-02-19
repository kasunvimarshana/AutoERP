<?php

declare(strict_types=1);

namespace Modules\Billing\Policies;

use Modules\Auth\Models\User;
use Modules\Billing\Models\Subscription;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('billing.subscriptions.view');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('billing.subscriptions.view')
            && $user->tenant_id === $subscription->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('billing.subscriptions.create');
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('billing.subscriptions.update')
            && $user->tenant_id === $subscription->tenant_id;
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('billing.subscriptions.delete')
            && $user->tenant_id === $subscription->tenant_id;
    }
}
