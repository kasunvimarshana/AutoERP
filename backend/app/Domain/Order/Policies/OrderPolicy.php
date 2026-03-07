<?php

declare(strict_types=1);

namespace App\Domain\Order\Policies;

use App\Domain\Auth\Entities\User;
use App\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * RBAC + ABAC policy for Order domain operations.
 */
final class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->sameOrSuperTenant($user, $order)
            && ($user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff'])
                || (int) $user->id === (int) $order->customer_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->sameOrSuperTenant($user, $order)
            && $user->hasAnyRole(['super-admin', 'admin', 'manager']);
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->sameOrSuperTenant($user, $order)
            && $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->sameOrSuperTenant($user, $order)
            && ($user->hasAnyRole(['super-admin', 'admin', 'manager'])
                || (int) $user->id === (int) $order->customer_id);
    }

    private function sameOrSuperTenant(User $user, Order $order): bool
    {
        return $user->hasRole('super-admin')
            || (int) $user->tenant_id === (int) $order->tenant_id;
    }
}
