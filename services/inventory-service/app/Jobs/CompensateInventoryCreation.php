<?php

namespace App\Jobs;

use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Compensating transaction for the ProductCreated saga.
 * Soft-deletes the inventory item that was created in a failed saga run.
 */
class CompensateInventoryCreation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 15;

    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $inventoryItemId,
        public readonly string $sagaId,
    ) {}

    public function handle(InventoryService $inventoryService): void
    {
        Log::info('CompensateInventoryCreation started', [
            'tenant_id'         => $this->tenantId,
            'inventory_item_id' => $this->inventoryItemId,
            'saga_id'           => $this->sagaId,
        ]);

        $result = $inventoryService->compensateCreation(
            $this->inventoryItemId,
            $this->tenantId,
            $this->sagaId
        );

        if ($result) {
            Log::info('CompensateInventoryCreation: compensation successful', [
                'inventory_item_id' => $this->inventoryItemId,
                'saga_id'           => $this->sagaId,
            ]);
        } else {
            Log::warning('CompensateInventoryCreation: item not found or already deleted', [
                'inventory_item_id' => $this->inventoryItemId,
                'saga_id'           => $this->sagaId,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CompensateInventoryCreation job failed permanently', [
            'inventory_item_id' => $this->inventoryItemId,
            'saga_id'           => $this->sagaId,
            'error'             => $exception->getMessage(),
        ]);
    }
}
