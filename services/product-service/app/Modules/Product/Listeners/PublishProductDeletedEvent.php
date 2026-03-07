<?php

namespace App\Modules\Product\Listeners;

use App\Modules\Product\Events\ProductDeleted;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class PublishProductDeletedEvent
{
    public function __construct(private RabbitMQService $rabbitMQService) {}

    public function handle(ProductDeleted $event): void
    {
        try {
            $this->rabbitMQService->publish(
                config('rabbitmq.exchange'),
                'product.deleted',
                [
                    'event' => 'ProductDeleted',
                    'data' => ['id' => $event->product->id, 'sku' => $event->product->sku],
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to publish ProductDeleted event: ' . $e->getMessage());
        }
    }
}
