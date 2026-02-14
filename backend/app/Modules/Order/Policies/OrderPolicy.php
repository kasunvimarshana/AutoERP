<?php

namespace App\Modules\Order\Policies;

use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.update');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.delete');
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.restore');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.cancel');
    }

    public function complete(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.complete');
    }
}
