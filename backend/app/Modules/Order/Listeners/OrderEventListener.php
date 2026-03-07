<?php

namespace App\Modules\Order\Listeners;

use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderCreated;
use App\Modules\Order\Events\OrderUpdated;
use App\Services\MessageBroker\MessageBrokerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class OrderEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function __construct(
        private readonly MessageBrokerInterface $messageBroker,
    ) {}

    public function handleOrderCreated(OrderCreated $event): void
    {
        $this->publish('order.created', $event->order);
    }

    public function handleOrderUpdated(OrderUpdated $event): void
    {
        $this->publish('order.updated', $event->order);
    }

    public function handleOrderCancelled(OrderCancelled $event): void
    {
        $this->publish('order.cancelled', $event->order);
    }

    private function publish(string $routingKey, $order): void
    {
        try {
            $this->messageBroker->publish('inventory.exchange', $routingKey, [
                'order_id'       => $order->id,
                'order_number'   => $order->order_number,
                'tenant_id'      => $order->tenant_id,
                'status'         => $order->status,
                'total'          => (float) $order->total,
                'timestamp'      => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to publish {$routingKey} event", ['error' => $e->getMessage()]);
        }
    }
}
