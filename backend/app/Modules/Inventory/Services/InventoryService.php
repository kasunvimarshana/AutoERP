<?php

namespace App\Modules\Inventory\Services;

use App\Enums\StockMovementType;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLedger;
use App\Modules\Inventory\Repositories\ProductRepository;
use App\Modules\Inventory\Repositories\StockLedgerRepository;
use App\Services\BaseService;

/**
 * Inventory Service
 *
 * Handles business logic for inventory management including
 * stock movements, valuation, and reporting.
 */
class InventoryService extends BaseService
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected StockLedgerRepository $stockLedgerRepository
    ) {}

    /**
     * Record a stock movement
     *
     * @throws \Throwable
     */
    public function recordStockMovement(StockMovementDTO $dto): StockLedger
    {
        return $this->transaction(function () use ($dto) {
            // Validate product exists
            $product = $this->productRepository->findOrFail($dto->productId);

            // Validate stock tracking
            if (! $product->requiresStockTracking()) {
                throw new \InvalidArgumentException('Product does not require stock tracking');
            }

            // Calculate quantity with proper sign
            $quantity = $dto->quantity * $dto->movementType->getSign();

            // Create stock ledger entry
            $ledgerData = [
                'tenant_id' => $product->tenant_id,
                'product_id' => $dto->productId,
                'movement_type' => $dto->movementType->value,
                'quantity' => $quantity,
                'unit_cost' => $dto->unitCost,
                'total_cost' => abs($quantity) * ($dto->unitCost ?? 0),
                'warehouse_id' => $dto->warehouseId,
                'location_id' => $dto->locationId,
                'batch_number' => $dto->batchNumber,
                'lot_number' => $dto->lotNumber,
                'serial_number' => $dto->serialNumber,
                'manufacturing_date' => $dto->manufacturingDate,
                'expiry_date' => $dto->expiryDate,
                'reference_type' => $dto->referenceType,
                'reference_id' => $dto->referenceId,
                'notes' => $dto->notes,
                'metadata' => $dto->metadata,
                'transaction_date' => $dto->transactionDate ?? now(),
            ];

            $stockLedger = $this->stockLedgerRepository->create($ledgerData);

            $this->logInfo('Stock movement recorded', [
                'product_id' => $dto->productId,
                'movement_type' => $dto->movementType->value,
                'quantity' => $quantity,
            ]);

            return $stockLedger;
        });
    }

    /**
     * Get current stock balance for a product
     */
    public function getCurrentStock(int $productId, ?int $warehouseId = null): float
    {
        return $this->stockLedgerRepository->calculateStockBalance($productId, $warehouseId);
    }

    /**
     * Adjust stock
     *
     * @throws \Throwable
     */
    public function adjustStock(
        int $productId,
        float $quantity,
        ?int $warehouseId = null,
        ?string $notes = null
    ): StockLedger {
        $movementType = $quantity >= 0
            ? StockMovementType::ADJUSTMENT_IN
            : StockMovementType::ADJUSTMENT_OUT;

        $dto = new StockMovementDTO(
            productId: $productId,
            movementType: $movementType,
            quantity: abs($quantity),
            warehouseId: $warehouseId,
            notes: $notes
        );

        return $this->recordStockMovement($dto);
    }

    /**
     * Transfer stock between warehouses
     *
     * @throws \Throwable
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        ?string $notes = null
    ): array {
        return $this->transaction(function () use (
            $productId,
            $fromWarehouseId,
            $toWarehouseId,
            $quantity,
            $notes
        ) {
            // Record outward movement from source warehouse
            $outDto = new StockMovementDTO(
                productId: $productId,
                movementType: StockMovementType::TRANSFER_OUT,
                quantity: $quantity,
                warehouseId: $fromWarehouseId,
                notes: $notes
            );
            $outMovement = $this->recordStockMovement($outDto);

            // Record inward movement to destination warehouse
            $inDto = new StockMovementDTO(
                productId: $productId,
                movementType: StockMovementType::TRANSFER_IN,
                quantity: $quantity,
                warehouseId: $toWarehouseId,
                notes: $notes
            );
            $inMovement = $this->recordStockMovement($inDto);

            return [
                'out_movement' => $outMovement,
                'in_movement' => $inMovement,
            ];
        });
    }

    /**
     * Get stock valuation for a product
     */
    public function getStockValuation(int $productId, ?int $warehouseId = null): float
    {
        return $this->stockLedgerRepository->getStockValuation($productId, $warehouseId);
    }

    /**
     * Get products below reorder level
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsBelowReorderLevel()
    {
        return $this->productRepository->getBelowReorderLevel();
    }

    /**
     * Get expiring stock for a product
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringStock(int $productId, int $days = 30)
    {
        return $this->stockLedgerRepository->getExpiringStock($productId, $days);
    }

    /**
     * Get stock movement history
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStockHistory(int $productId, ?int $warehouseId = null)
    {
        return $this->stockLedgerRepository->getProductLedger($productId, $warehouseId);
    }

    /**
     * Verify stock availability
     */
    public function verifyStockAvailability(
        int $productId,
        float $requiredQuantity,
        ?int $warehouseId = null
    ): bool {
        $currentStock = $this->getCurrentStock($productId, $warehouseId);

        return $currentStock >= $requiredQuantity;
    }
}
