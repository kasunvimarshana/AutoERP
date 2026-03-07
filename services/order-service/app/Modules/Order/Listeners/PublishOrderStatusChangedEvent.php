<?php

namespace App\Modules\Order\Listeners;

use App\Modules\Order\Events\OrderStatusChanged;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class PublishOrderStatusChangedEvent
{
    public function __construct(private RabbitMQService $rabbitMQService) {}

    public function handle(OrderStatusChanged $event): void
    {
        try {
            $this->rabbitMQService->publish(
                config('rabbitmq.exchange'),
                'order.status_changed',
                [
                    'event' => 'OrderStatusChanged',
                    'data' => [
                        'order_id' => $event->order->id,
                        'order_number' => $event->order->order_number,
                        'previous_status' => $event->previousStatus,
                        'new_status' => $event->newStatus,
                        'order' => $event->order->toArray(),
                    ],
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to publish OrderStatusChanged event: ' . $e->getMessage());
        }
    }
}
