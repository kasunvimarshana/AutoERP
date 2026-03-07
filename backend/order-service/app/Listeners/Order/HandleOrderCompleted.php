<?php

namespace App\Listeners\Order;

use App\Events\OrderCompleted;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleOrderCompleted implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;
        $this->broker->publish('order.completed', [
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'user_id' => $order->user_id,
            'total_amount' => $order->total_amount,
            'status' => $order->status,
            'completed_at' => $order->completed_at?->toIso8601String(),
            'items' => $order->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity' => $i->quantity,
                'subtotal' => $i->subtotal,
            ])->toArray(),
        ]);
    }
}
