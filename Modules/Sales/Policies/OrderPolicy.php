<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Modules\Auth\Models\User;
use Modules\Sales\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.view')
            && $user->tenant_id === $order->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.update')
            && $user->tenant_id === $order->tenant_id;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.delete')
            && $user->tenant_id === $order->tenant_id;
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.delete')
            && $user->tenant_id === $order->tenant_id;
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.force_delete')
            && $user->tenant_id === $order->tenant_id;
    }
}
