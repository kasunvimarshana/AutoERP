<?php

namespace App\Modules\Product\Listeners;

use App\Modules\Product\Events\ProductCreated;
use App\Modules\Product\Events\ProductDeleted;
use App\Modules\Product\Events\ProductUpdated;
use App\Services\MessageBroker\MessageBrokerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProductEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function __construct(
        private readonly MessageBrokerInterface $messageBroker,
    ) {}

    public function handleProductCreated(ProductCreated $event): void
    {
        $this->publish('product.created', [
            'product_id' => $event->product->id,
            'tenant_id'  => $event->product->tenant_id,
            'name'       => $event->product->name,
            'sku'        => $event->product->sku,
        ]);
    }

    public function handleProductUpdated(ProductUpdated $event): void
    {
        $this->publish('product.updated', [
            'product_id' => $event->product->id,
            'tenant_id'  => $event->product->tenant_id,
            'name'       => $event->product->name,
            'sku'        => $event->product->sku,
        ]);
    }

    public function handleProductDeleted(ProductDeleted $event): void
    {
        $this->publish('product.deleted', [
            'product_id' => $event->productId,
            'tenant_id'  => $event->tenantId,
        ]);
    }

    private function publish(string $routingKey, array $payload): void
    {
        try {
            $this->messageBroker->publish('inventory.exchange', $routingKey, array_merge($payload, [
                'timestamp' => now()->toIso8601String(),
            ]));
        } catch (\Throwable $e) {
            Log::error("Failed to publish {$routingKey} event", ['error' => $e->getMessage()]);
        }
    }
}
