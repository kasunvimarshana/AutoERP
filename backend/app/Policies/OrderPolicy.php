<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends BasePolicy
{
    protected function module(): string
    {
        return 'orders';
    }

    /**
     * Users can always view their own orders.
     */
    public function view(User $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        /** @var Order $model */
        if ($model instanceof Order && $user->id === $model->user_id) {
            return true;
        }

        return parent::view($user, $model);
    }
}
