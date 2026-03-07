<?php

namespace App\Jobs;

use App\DTOs\SagaStateDTO;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Saga step: create a default inventory record when a product.created event is received.
 * On failure, dispatches CompensateInventoryCreation to undo any partial work.
 */
class ProcessProductCreatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $tries   = 3;
    public int    $backoff = 10;

    private SagaStateDTO $saga;

    public function __construct(
        public readonly int   $tenantId,
        public readonly int   $productId,
        public readonly array $payload,
    ) {
        $this->saga = new SagaStateDTO(
            sagaId:   Str::uuid()->toString(),
            sagaType: 'product_created',
            tenantId: $tenantId,
            context:  ['product_id' => $productId, 'payload' => $payload],
        );

        $this->saga
            ->addStep('resolve_default_warehouse')
            ->addStep('create_inventory_item');
    }

    public function handle(
        InventoryRepositoryInterface  $inventoryRepository,
        WarehouseRepositoryInterface  $warehouseRepository,
    ): void {
        Log::info('ProcessProductCreatedJob started', $this->saga->toArray());

        $createdItemId = null;

        try {
            // Step 1: resolve the default (first active) warehouse for the tenant
            $this->saga->startStep('resolve_default_warehouse');

            $warehouses = $warehouseRepository->getActiveForTenant($this->tenantId);
            $warehouse  = $warehouses->first();

            if (! $warehouse) {
                throw new \RuntimeException(
                    "No active warehouse found for tenant [{$this->tenantId}]. Cannot create inventory."
                );
            }

            $this->saga->completeStep('resolve_default_warehouse');

            // Step 2: create the inventory record
            $this->saga->startStep('create_inventory_item');

            $sku = 'PROD-' . $this->productId . '-' . strtoupper(Str::random(6));

            // Idempotency check — skip if already exists
            $existing = $inventoryRepository->findByProductAndWarehouse(
                $this->productId,
                $warehouse->id,
                $this->tenantId
            );

            if ($existing) {
                $this->saga->completeStep('create_inventory_item');
                $this->saga->complete();

                Log::info('ProcessProductCreatedJob: inventory item already exists (idempotent)', [
                    'inventory_item_id' => $existing->id,
                    'saga_id'           => $this->saga->sagaId,
                ]);

                return;
            }

            $item = $inventoryRepository->create([
                'tenant_id'         => $this->tenantId,
                'product_id'        => $this->productId,
                'warehouse_id'      => $warehouse->id,
                'sku'               => $sku,
                'quantity'          => 0,
                'reserved_quantity' => 0,
                'reorder_point'     => 0,
                'reorder_quantity'  => 0,
            ]);

            $createdItemId = $item->id;

            $this->saga->completeStep('create_inventory_item');
            $this->saga->complete();

            Log::info('ProcessProductCreatedJob completed', [
                'inventory_item_id' => $item->id,
                'saga_id'           => $this->saga->sagaId,
            ]);
        } catch (\Throwable $e) {
            $this->saga->failStep(
                $this->saga->getFailedStep() ?? 'unknown',
                $e->getMessage()
            );

            Log::error('ProcessProductCreatedJob failed — triggering compensation', [
                'saga_id' => $this->saga->sagaId,
                'error'   => $e->getMessage(),
            ]);

            // Fire compensating transaction if we created anything
            if ($createdItemId !== null) {
                CompensateInventoryCreation::dispatch(
                    tenantId:        $this->tenantId,
                    inventoryItemId: $createdItemId,
                    sagaId:          $this->saga->sagaId,
                );
            }

            $this->fail($e);
        }
    }
}
