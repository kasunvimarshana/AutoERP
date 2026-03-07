<?php

namespace App\Listeners;

use App\Events\ProductDeleted;
use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyInventoryOnProductDeleted implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'inventory-events';

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(private readonly MessageBrokerInterface $broker) {}

    public function handle(ProductDeleted $event): void
    {
        $product = $event->product;

        $payload = [
            'event'      => 'product.deleted',
            'product_id' => $product->id,
            'sku'        => $product->sku,
            'tenant_id'  => $product->tenant_id,
            'timestamp'  => now()->toIso8601String(),
        ];

        try {
            $published = $this->broker->publish(
                topic:   config('services.topics.inventory', 'inventory.products'),
                payload: $payload,
            );

            if ($published) {
                Log::info('Inventory notified of product deletion', [
                    'product_id' => $product->id,
                    'tenant_id'  => $product->tenant_id,
                ]);
            } else {
                Log::warning('Failed to notify inventory of product deletion', [
                    'product_id' => $product->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('NotifyInventoryOnProductDeleted error', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
