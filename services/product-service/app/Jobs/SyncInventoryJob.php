<?php

namespace App\Jobs;

use App\Services\InventoryClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Saga Step 1: Sync a newly created product with the inventory-service.
 * On failure, dispatches CompensateProductCreation to roll back.
 */
class SyncInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 15;

    public function __construct(
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $tenantId,
        public readonly string $triggeredBy,
    ) {}

    public function handle(InventoryClientService $inventoryClient): void
    {
        try {
            $inventoryClient->createInventoryRecord(
                productId:  $this->productId,
                sku:        $this->sku,
                tenantId:   $this->tenantId,
            );

            Log::info('Inventory record created for new product', [
                'product_id' => $this->productId,
                'sku'        => $this->sku,
                'tenant_id'  => $this->tenantId,
            ]);
        } catch (\Throwable $e) {
            Log::error('SyncInventoryJob failed', [
                'product_id' => $this->productId,
                'error'      => $e->getMessage(),
                'attempt'    => $this->attempts(),
            ]);

            // If all retries exhausted, dispatch compensating transaction
            if ($this->attempts() >= $this->tries) {
                CompensateProductCreation::dispatch(
                    $this->productId,
                    $this->sku,
                    $this->tenantId,
                    $this->triggeredBy,
                    reason: $e->getMessage(),
                );
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('SyncInventoryJob permanently failed — dispatching compensation', [
            'product_id' => $this->productId,
            'sku'        => $this->sku,
            'error'      => $exception->getMessage(),
        ]);

        CompensateProductCreation::dispatch(
            $this->productId,
            $this->sku,
            $this->tenantId,
            $this->triggeredBy,
            reason: $exception->getMessage(),
        );
    }
}
