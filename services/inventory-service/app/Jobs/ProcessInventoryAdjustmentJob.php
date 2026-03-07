<?php

namespace App\Jobs;

use App\DTOs\StockAdjustmentDTO;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Asynchronously process a stock adjustment.
 * Useful when adjustments are triggered by batch processes or external events.
 */
class ProcessInventoryAdjustmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 5;

    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $inventoryItemId,
        public readonly string $adjustmentType,
        public readonly int    $quantity,
        public readonly string $reason,
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId   = null,
        public readonly ?int    $performedBy   = null,
        public readonly ?array  $metadata      = null,
    ) {}

    public function handle(
        InventoryRepositoryInterface $inventoryRepository,
        InventoryService             $inventoryService,
    ): void {
        $item = $inventoryRepository->findById($this->inventoryItemId, $this->tenantId);

        if (! $item) {
            Log::warning('ProcessInventoryAdjustmentJob: item not found', [
                'inventory_item_id' => $this->inventoryItemId,
                'tenant_id'         => $this->tenantId,
            ]);

            return;
        }

        $dto = new StockAdjustmentDTO(
            type:          $this->adjustmentType,
            quantity:      $this->quantity,
            reason:        $this->reason,
            referenceType: $this->referenceType,
            referenceId:   $this->referenceId,
            performedBy:   $this->performedBy,
            metadata:      $this->metadata,
        );

        try {
            $inventoryService->adjustStock($item, $dto);

            Log::info('ProcessInventoryAdjustmentJob completed', [
                'inventory_item_id' => $this->inventoryItemId,
                'type'              => $this->adjustmentType,
                'quantity'          => $this->quantity,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessInventoryAdjustmentJob failed', [
                'inventory_item_id' => $this->inventoryItemId,
                'error'             => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
