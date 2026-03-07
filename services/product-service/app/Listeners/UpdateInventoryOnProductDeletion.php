<?php

namespace App\Listeners;

use App\Events\ProductDeleted;
use App\Services\InventoryClientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdateInventoryOnProductDeletion implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(private readonly InventoryClientService $inventoryClient) {}

    public function handle(ProductDeleted $event): void
    {
        try {
            $this->inventoryClient->markProductDeleted(
                productId: $event->productId,
                tenantId:  $event->tenantId,
            );

            Log::info('Inventory notified of product deletion', [
                'product_id' => $event->productId,
                'sku'        => $event->sku,
                'tenant_id'  => $event->tenantId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to notify inventory of product deletion — compensating', [
                'product_id' => $event->productId,
                'error'      => $e->getMessage(),
            ]);
            // Re-throw so the queue retries, preserving at-least-once delivery.
            throw $e;
        }
    }

    public function failed(ProductDeleted $event, \Throwable $exception): void
    {
        Log::critical('Compensating transaction required: inventory not updated for deleted product', [
            'product_id' => $event->productId,
            'sku'        => $event->sku,
            'tenant_id'  => $event->tenantId,
            'error'      => $exception->getMessage(),
        ]);
    }
}
