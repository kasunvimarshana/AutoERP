<?php

namespace App\Modules\Order\Listeners;

use App\Modules\Order\Events\OrderUpdated;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class PublishOrderUpdatedEvent
{
    public function __construct(private RabbitMQService $rabbitMQService) {}

    public function handle(OrderUpdated $event): void
    {
        try {
            $this->rabbitMQService->publish(
                config('rabbitmq.exchange'),
                'order.updated',
                [
                    'event' => 'OrderUpdated',
                    'data' => array_merge(
                        $event->order->toArray(),
                        ['items' => $event->order->items->toArray()]
                    ),
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to publish OrderUpdated event: ' . $e->getMessage());
        }
    }
}
