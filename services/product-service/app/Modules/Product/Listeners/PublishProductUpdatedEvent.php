<?php

namespace App\Modules\Product\Listeners;

use App\Modules\Product\Events\ProductUpdated;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class PublishProductUpdatedEvent
{
    public function __construct(private RabbitMQService $rabbitMQService) {}

    public function handle(ProductUpdated $event): void
    {
        try {
            $this->rabbitMQService->publish(
                config('rabbitmq.exchange'),
                'product.updated',
                [
                    'event' => 'ProductUpdated',
                    'data' => $event->product->toArray(),
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to publish ProductUpdated event: ' . $e->getMessage());
        }
    }
}
