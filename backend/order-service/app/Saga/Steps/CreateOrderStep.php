<?php

namespace App\Saga\Steps;

use App\Models\Order;
use App\Saga\Contracts\SagaStepInterface;
use Illuminate\Support\Facades\DB;

class CreateOrderStep implements SagaStepInterface
{
    public function getName(): string { return 'create_order'; }

    public function execute(Order $order, array $context = []): array
    {
        DB::transaction(fn() => $order->update(['status' => Order::STATUS_PROCESSING]));
        return ['order_id' => $order->id, 'items' => $order->items->toArray()];
    }

    public function compensate(Order $order, array $context = []): void
    {
        DB::transaction(fn() => $order->update(['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now()]));
    }
}
