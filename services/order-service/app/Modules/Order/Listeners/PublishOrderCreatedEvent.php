<?php

namespace App\Modules\Order\Listeners;

use App\Modules\Order\Events\OrderCreated;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class PublishOrderCreatedEvent
{
    public function __construct(private RabbitMQService $rabbitMQService) {}

    public function handle(OrderCreated $event): void
    {
        try {
            $this->rabbitMQService->publish(
                config('rabbitmq.exchange'),
                'order.created',
                [
                    'event' => 'OrderCreated',
                    'data' => array_merge(
                        $event->order->toArray(),
                        ['items' => $event->order->items->toArray()]
                    ),
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to publish OrderCreated event: ' . $e->getMessage());
        }
    }
}
