<?php

namespace App\Modules\Order\Listeners;

use App\Modules\Order\Events\OrderCancelled;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class PublishOrderCancelledEvent
{
    public function __construct(private RabbitMQService $rabbitMQService) {}

    public function handle(OrderCancelled $event): void
    {
        try {
            // Saga compensating transaction: notify inventory to release reserved stock
            $this->rabbitMQService->publish(
                config('rabbitmq.exchange'),
                'order.cancelled',
                [
                    'event' => 'OrderCancelled',
                    'data' => [
                        'order_id' => $event->order->id,
                        'order_number' => $event->order->order_number,
                        'user_id' => $event->order->user_id,
                        'items' => $event->order->items->map(fn ($item) => [
                            'product_id' => $item->product_id,
                            'product_sku' => $item->product_sku,
                            'quantity' => $item->quantity,
                        ])->toArray(),
                    ],
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to publish OrderCancelled event: ' . $e->getMessage());
        }
    }
}
