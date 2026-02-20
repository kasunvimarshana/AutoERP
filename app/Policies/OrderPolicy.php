<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view') || $user->hasRole(['admin', 'manager', 'staff']);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->tenant_id === $order->tenant_id
            && ($user->can('orders.view') || $user->hasRole(['admin', 'manager', 'staff']));
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create') || $user->hasRole(['admin', 'manager', 'staff']);
    }

    public function update(User $user, Order $order): bool
    {
        return $user->tenant_id === $order->tenant_id
            && ($user->can('orders.update') || $user->hasRole(['admin', 'manager']));
    }

    public function confirm(User $user, Order $order): bool
    {
        return $user->tenant_id === $order->tenant_id
            && ($user->can('orders.confirm') || $user->hasRole(['admin', 'manager']));
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->tenant_id === $order->tenant_id
            && ($user->can('orders.cancel') || $user->hasRole(['admin', 'manager']));
    }
}
