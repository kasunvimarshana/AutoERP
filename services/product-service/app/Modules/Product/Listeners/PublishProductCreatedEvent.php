<?php

namespace App\Modules\Product\Listeners;

use App\Modules\Product\Events\ProductCreated;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class PublishProductCreatedEvent
{
    public function __construct(private RabbitMQService $rabbitMQService) {}

    public function handle(ProductCreated $event): void
    {
        try {
            $this->rabbitMQService->publish(
                config('rabbitmq.exchange'),
                'product.created',
                [
                    'event' => 'ProductCreated',
                    'data' => $event->product->toArray(),
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to publish ProductCreated event: ' . $e->getMessage());
        }
    }
}
