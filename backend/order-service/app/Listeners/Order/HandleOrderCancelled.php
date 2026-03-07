<?php

namespace App\Listeners\Order;

use App\Events\OrderCancelled;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleOrderCancelled implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(OrderCancelled $event): void
    {
        $this->broker->publish('order.cancelled', [
            'order_id' => $event->order->id,
            'tenant_id' => $event->order->tenant_id,
            'status' => $event->order->status,
            'cancelled_at' => $event->order->cancelled_at?->toIso8601String(),
        ]);
    }
}
