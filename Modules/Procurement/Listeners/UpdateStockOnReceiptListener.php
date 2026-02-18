<?php

declare(strict_types=1);

namespace Modules\Procurement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Repositories\Contracts\StockLevelRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Modules\Procurement\Events\GoodsReceived;

/**
 * Update Stock On Receipt Listener
 *
 * Updates inventory stock levels when goods are received from suppliers.
 * Creates stock movement records for tracking inventory changes.
 * Implements event-driven integration between Procurement and Inventory modules.
 */
class UpdateStockOnReceiptListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying
     */
    public int $backoff = 10;

    /**
     * Create a new listener instance
     */
    public function __construct(
        private StockMovementRepositoryInterface $stockMovementRepository,
        private StockLevelRepositoryInterface $stockLevelRepository
    ) {}

    /**
     * Handle the event
     */
    public function handle(GoodsReceived $event): void
    {
        DB::beginTransaction();
        try {
            $goodsReceipt = $event->goodsReceipt;

            // Process each line item in the goods receipt
            foreach ($goodsReceipt->lines as $line) {
                if (! $line->product_id) {
                    continue;
                }

                // Create stock movement record
                $stockMovement = $this->stockMovementRepository->create([
                    'tenant_id' => $goodsReceipt->tenant_id,
                    'product_id' => $line->product_id,
                    'warehouse_id' => $goodsReceipt->warehouse_id ?? null,
                    'quantity' => $line->received_quantity, // Positive for receipt
                    'unit_cost' => $line->unit_price,
                    'movement_type' => 'RECEIPT',
                    'reference_type' => 'goods_receipt',
                    'reference_id' => $goodsReceipt->id,
                    'reference_number' => $goodsReceipt->receipt_number,
                    'notes' => "Goods received from {$goodsReceipt->supplier->name}",
                    'status' => 'completed',
                    'movement_date' => $goodsReceipt->receipt_date,
                ]);

                // Update stock level
                $this->updateStockLevel(
                    $goodsReceipt->tenant_id,
                    $line->product_id,
                    $goodsReceipt->warehouse_id,
                    $line->received_quantity,
                    $line->unit_price
                );

                Log::info('Stock movement created for goods receipt', [
                    'goods_receipt_id' => $goodsReceipt->id,
                    'product_id' => $line->product_id,
                    'quantity' => $line->received_quantity,
                    'stock_movement_id' => $stockMovement->id,
                ]);
            }

            DB::commit();

            Log::info('Stock updated for goods receipt', [
                'goods_receipt_id' => $goodsReceipt->id,
                'receipt_number' => $goodsReceipt->receipt_number,
                'supplier_id' => $goodsReceipt->supplier_id,
                'lines_count' => $goodsReceipt->lines->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update stock for goods receipt', [
                'goods_receipt_id' => $event->goodsReceipt->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Update stock level for a product in a warehouse
     */
    private function updateStockLevel(
        string $tenantId,
        string $productId,
        ?string $warehouseId,
        float $quantity,
        float $unitCost
    ): void {
        // Find existing stock level or create new one
        $stockLevel = $this->stockLevelRepository->findByProductAndWarehouse(
            $productId,
            $warehouseId
        );

        if ($stockLevel) {
            // Calculate new average cost using weighted average method
            $currentValue = $stockLevel->quantity * $stockLevel->average_cost;
            $newValue = $quantity * $unitCost;
            $totalQuantity = $stockLevel->quantity + $quantity;
            $newAverageCost = $totalQuantity > 0
                ? ($currentValue + $newValue) / $totalQuantity
                : $unitCost;

            // Update existing stock level
            $this->stockLevelRepository->update($stockLevel, [
                'quantity' => $totalQuantity,
                'available_quantity' => $stockLevel->available_quantity + $quantity,
                'average_cost' => $newAverageCost,
                'last_receipt_date' => now(),
            ]);
        } else {
            // Create new stock level
            $this->stockLevelRepository->create([
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'available_quantity' => $quantity,
                'reserved_quantity' => 0,
                'average_cost' => $unitCost,
                'last_receipt_date' => now(),
            ]);
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(GoodsReceived $event, \Throwable $exception): void
    {
        Log::error('Stock update for goods receipt failed permanently', [
            'goods_receipt_id' => $event->goodsReceipt->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
