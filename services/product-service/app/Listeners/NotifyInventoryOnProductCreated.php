<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyInventoryOnProductCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'inventory-events';

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(private readonly MessageBrokerInterface $broker) {}

    public function handle(ProductCreated $event): void
    {
        $product = $event->product;

        $payload = [
            'event'      => 'product.created',
            'product_id' => $product->id,
            'sku'        => $product->sku,
            'name'       => $product->name,
            'tenant_id'  => $product->tenant_id,
            'category_id' => $product->category_id,
            'min_stock_level' => $product->min_stock_level,
            'max_stock_level' => $product->max_stock_level,
            'reorder_point'   => $product->reorder_point,
            'timestamp'  => now()->toIso8601String(),
        ];

        try {
            $published = $this->broker->publish(
                topic:   config('services.topics.inventory', 'inventory.products'),
                payload: $payload,
            );

            if ($published) {
                Log::info('Inventory notified of product creation', [
                    'product_id' => $product->id,
                    'tenant_id'  => $product->tenant_id,
                ]);
            } else {
                Log::warning('Failed to notify inventory of product creation', [
                    'product_id' => $product->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('NotifyInventoryOnProductCreated error', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
