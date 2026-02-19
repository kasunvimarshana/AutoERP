<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Helpers\MathHelper;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Inventory\Enums\StockMovementType;
use Modules\Inventory\Events\ReorderPointReached;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Events\StockIssued;
use Modules\Inventory\Events\StockReceived;
use Modules\Inventory\Events\StockReleased;
use Modules\Inventory\Events\StockReserved;
use Modules\Inventory\Events\StockTransferred;
use Modules\Inventory\Exceptions\InsufficientStockException;
use Modules\Inventory\Exceptions\InvalidStockOperationException;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Repositories\StockItemRepository;
use Modules\Inventory\Repositories\StockMovementRepository;
use Modules\Inventory\Repositories\WarehouseRepository;

/**
 * Stock Movement Service
 *
 * Handles all stock movements including receive, issue, transfer, adjust,
 * reserve, and release operations. Validates quantities, prevents negative
 * stock, and maintains transaction integrity.
 */
class StockMovementService
{
    public function __construct(
        private StockMovementRepository $stockMovementRepository,
        private StockItemRepository $stockItemRepository,
        private WarehouseRepository $warehouseRepository
    ) {}

    /**
     * Process stock receipt (goods received into warehouse).
     *
     * @param  array  $data  Movement data
     */
    public function processReceipt(array $data): StockMovement
    {
        return TransactionHelper::execute(function () use ($data) {
            // Validate warehouse can accept stock
            $warehouse = $this->warehouseRepository->findOrFail($data['to_warehouse_id']);
            if (! $warehouse->canAcceptStock()) {
                throw new InvalidStockOperationException(
                    "Warehouse {$warehouse->name} cannot accept stock"
                );
            }

            // Validate quantity
            $this->validatePositiveQuantity($data['quantity']);

            // Create movement record
            $movement = $this->stockMovementRepository->create([
                'tenant_id' => $data['tenant_id'],
                'organization_id' => $data['organization_id'] ?? null,
                'product_id' => $data['product_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'from_warehouse_id' => null,
                'type' => StockMovementType::RECEIPT,
                'quantity' => $data['quantity'],
                'cost' => $data['unit_cost'] ?? $data['cost'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Update stock item
            $this->increaseStock(
                $data['product_id'],
                $data['to_warehouse_id'],
                $data['quantity'],
                $data['unit_cost'] ?? $data['cost'] ?? null
            );

            // Fire event
            event(new StockReceived($movement));

            return $movement->load(['product', 'toWarehouse']);
        });
    }

    /**
     * Process stock issue (goods issued from warehouse).
     *
     * @param  array  $data  Movement data
     */
    public function processIssue(array $data): StockMovement
    {
        return TransactionHelper::execute(function () use ($data) {
            // Validate warehouse can issue stock
            $warehouse = $this->warehouseRepository->findOrFail($data['from_warehouse_id']);
            if (! $warehouse->canIssueStock()) {
                throw new InvalidStockOperationException(
                    "Warehouse {$warehouse->name} cannot issue stock"
                );
            }

            // Validate quantity
            $this->validatePositiveQuantity($data['quantity']);

            // Check available stock
            $this->validateSufficientStock(
                $data['product_id'],
                $data['from_warehouse_id'],
                $data['quantity']
            );

            // Create movement record
            $movement = $this->stockMovementRepository->create([
                'tenant_id' => $data['tenant_id'],
                'organization_id' => $data['organization_id'] ?? null,
                'product_id' => $data['product_id'],
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => null,
                'type' => StockMovementType::ISSUE,
                'quantity' => $data['quantity'],
                'cost' => $data['unit_cost'] ?? $data['cost'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Update stock item
            $this->decreaseStock(
                $data['product_id'],
                $data['from_warehouse_id'],
                $data['quantity']
            );

            // Fire event
            event(new StockIssued($movement));

            return $movement->load(['product', 'fromWarehouse']);
        });
    }

    /**
     * Process stock transfer (goods moved between warehouses).
     *
     * @param  array  $data  Movement data
     */
    public function processTransfer(array $data): StockMovement
    {
        return TransactionHelper::execute(function () use ($data) {
            // Validate warehouses
            $fromWarehouse = $this->warehouseRepository->findOrFail($data['from_warehouse_id']);
            $toWarehouse = $this->warehouseRepository->findOrFail($data['to_warehouse_id']);

            if ($data['from_warehouse_id'] === $data['to_warehouse_id']) {
                throw new InvalidStockOperationException(
                    'Cannot transfer stock to the same warehouse'
                );
            }

            if (! $fromWarehouse->canIssueStock()) {
                throw new InvalidStockOperationException(
                    "Source warehouse {$fromWarehouse->name} cannot issue stock"
                );
            }

            if (! $toWarehouse->canAcceptStock()) {
                throw new InvalidStockOperationException(
                    "Destination warehouse {$toWarehouse->name} cannot accept stock"
                );
            }

            // Validate quantity
            $this->validatePositiveQuantity($data['quantity']);

            // Check available stock in source warehouse
            $this->validateSufficientStock(
                $data['product_id'],
                $data['from_warehouse_id'],
                $data['quantity']
            );

            // Create movement record
            $movement = $this->stockMovementRepository->create([
                'tenant_id' => $data['tenant_id'],
                'organization_id' => $data['organization_id'] ?? null,
                'product_id' => $data['product_id'],
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'type' => StockMovementType::TRANSFER,
                'quantity' => $data['quantity'],
                'cost' => $data['unit_cost'] ?? $data['cost'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Update stock items
            $this->decreaseStock($data['product_id'], $data['from_warehouse_id'], $data['quantity']);
            $this->increaseStock(
                $data['product_id'],
                $data['to_warehouse_id'],
                $data['quantity'],
                $data['unit_cost'] ?? $data['cost'] ?? null
            );

            // Fire event
            event(new StockTransferred($movement));

            return $movement->load(['product', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * Process stock adjustment (manual correction).
     *
     * @param  array  $data  Movement data
     */
    public function processAdjustment(array $data): StockMovement
    {
        return TransactionHelper::execute(function () use ($data) {
            $warehouse = $this->warehouseRepository->findOrFail($data['warehouse_id']);
            $adjustmentQuantity = (string) $data['adjustment_quantity'];

            // Validate adjustment is not zero
            if (MathHelper::equals($adjustmentQuantity, '0')) {
                throw new InvalidStockOperationException('Adjustment quantity cannot be zero');
            }

            $isIncrease = MathHelper::greaterThan($adjustmentQuantity, '0');
            $absoluteQuantity = MathHelper::abs($adjustmentQuantity);

            // If decreasing, validate sufficient stock
            if (! $isIncrease) {
                $this->validateSufficientStock(
                    $data['product_id'],
                    $data['warehouse_id'],
                    $absoluteQuantity
                );
            }

            // Create movement record
            $movement = $this->stockMovementRepository->create([
                'tenant_id' => $data['tenant_id'],
                'organization_id' => $data['organization_id'] ?? null,
                'product_id' => $data['product_id'],
                'from_warehouse_id' => $isIncrease ? null : $data['warehouse_id'],
                'to_warehouse_id' => $isIncrease ? $data['warehouse_id'] : null,
                'type' => StockMovementType::ADJUSTMENT,
                'quantity' => $absoluteQuantity,
                'cost' => $data['unit_cost'] ?? $data['cost'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Update stock item
            if ($isIncrease) {
                $this->increaseStock(
                    $data['product_id'],
                    $data['warehouse_id'],
                    $absoluteQuantity,
                    $data['unit_cost'] ?? $data['cost'] ?? null
                );
            } else {
                $this->decreaseStock($data['product_id'], $data['warehouse_id'], $absoluteQuantity);
            }

            // Fire event
            event(new StockAdjusted($movement));

            return $movement->load(['product', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * Reserve stock for orders or allocations.
     *
     * @param  array  $data  Movement data
     */
    public function reserveStock(array $data): StockMovement
    {
        return TransactionHelper::execute(function () use ($data) {
            // Validate quantity
            $this->validatePositiveQuantity($data['quantity']);

            // Check available (unreserved) stock
            $this->validateSufficientAvailableStock(
                $data['product_id'],
                $data['warehouse_id'],
                $data['quantity']
            );

            // Create movement record
            $movement = $this->stockMovementRepository->create([
                'tenant_id' => $data['tenant_id'],
                'organization_id' => $data['organization_id'] ?? null,
                'product_id' => $data['product_id'],
                'from_warehouse_id' => $data['warehouse_id'],
                'to_warehouse_id' => null,
                'type' => StockMovementType::RESERVED,
                'quantity' => $data['quantity'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Update reserved quantity
            $this->reserveStockQuantity($data['product_id'], $data['warehouse_id'], $data['quantity']);

            // Fire event
            event(new StockReserved($movement));

            return $movement->load(['product', 'fromWarehouse']);
        });
    }

    /**
     * Release reserved stock.
     *
     * @param  array  $data  Movement data
     */
    public function releaseStock(array $data): StockMovement
    {
        return TransactionHelper::execute(function () use ($data) {
            // Validate quantity
            $this->validatePositiveQuantity($data['quantity']);

            // Create movement record
            $movement = $this->stockMovementRepository->create([
                'tenant_id' => $data['tenant_id'],
                'organization_id' => $data['organization_id'] ?? null,
                'product_id' => $data['product_id'],
                'from_warehouse_id' => null,
                'to_warehouse_id' => $data['warehouse_id'],
                'type' => StockMovementType::RELEASED,
                'quantity' => $data['quantity'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'movement_date' => $data['movement_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Update reserved quantity
            $this->releaseStockQuantity($data['product_id'], $data['warehouse_id'], $data['quantity']);

            // Fire event
            event(new StockReleased($movement));

            return $movement->load(['product', 'toWarehouse']);
        });
    }

    /**
     * Increase stock quantity in warehouse.
     */
    private function increaseStock(string $productId, string $warehouseId, string $quantity, ?string $unitCost = null): void
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouse($productId, $warehouseId);

        if (! $stockItem) {
            // Create new stock item
            $this->stockItemRepository->create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'available_quantity' => $quantity,
                'reserved_quantity' => '0',
                'average_cost' => $unitCost ?? '0',
            ]);
        } else {
            // Update existing stock item
            $newQuantity = MathHelper::add($stockItem->quantity, $quantity);
            $newAvailable = MathHelper::add($stockItem->available_quantity, $quantity);

            $updateData = [
                'quantity' => $newQuantity,
                'available_quantity' => $newAvailable,
            ];

            // Update average cost if unit cost is provided
            if ($unitCost !== null && MathHelper::greaterThan($unitCost, '0')) {
                $totalValue = MathHelper::multiply($stockItem->quantity, $stockItem->average_cost);
                $addedValue = MathHelper::multiply($quantity, $unitCost);
                $newTotalValue = MathHelper::add($totalValue, $addedValue);
                $updateData['average_cost'] = MathHelper::divide($newTotalValue, $newQuantity);
            }

            $this->stockItemRepository->update($stockItem->id, $updateData);
        }
    }

    /**
     * Decrease stock quantity in warehouse.
     */
    private function decreaseStock(string $productId, string $warehouseId, string $quantity): void
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouseOrFail($productId, $warehouseId);

        $newQuantity = MathHelper::subtract($stockItem->quantity, $quantity);
        $newAvailable = MathHelper::subtract($stockItem->available_quantity, $quantity);

        $this->stockItemRepository->update($stockItem->id, [
            'quantity' => $newQuantity,
            'available_quantity' => $newAvailable,
        ]);

        // Check reorder point
        $this->checkReorderPoint($stockItem, $newAvailable);
    }

    /**
     * Reserve stock quantity.
     */
    private function reserveStockQuantity(string $productId, string $warehouseId, string $quantity): void
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouseOrFail($productId, $warehouseId);

        $newReserved = MathHelper::add($stockItem->reserved_quantity, $quantity);
        $newAvailable = MathHelper::subtract($stockItem->available_quantity, $quantity);

        $this->stockItemRepository->update($stockItem->id, [
            'reserved_quantity' => $newReserved,
            'available_quantity' => $newAvailable,
        ]);
    }

    /**
     * Release reserved stock quantity.
     */
    private function releaseStockQuantity(string $productId, string $warehouseId, string $quantity): void
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouseOrFail($productId, $warehouseId);

        $newReserved = MathHelper::subtract($stockItem->reserved_quantity, $quantity);
        $newAvailable = MathHelper::add($stockItem->available_quantity, $quantity);

        $this->stockItemRepository->update($stockItem->id, [
            'reserved_quantity' => MathHelper::max($newReserved, '0'),
            'available_quantity' => $newAvailable,
        ]);
    }

    /**
     * Validate quantity is positive.
     *
     * @throws InvalidStockOperationException
     */
    private function validatePositiveQuantity(string $quantity): void
    {
        if (MathHelper::lessThan($quantity, '0') || MathHelper::equals($quantity, '0')) {
            throw new InvalidStockOperationException('Quantity must be greater than zero');
        }
    }

    /**
     * Validate sufficient stock is available.
     *
     * @throws InsufficientStockException
     */
    private function validateSufficientStock(string $productId, string $warehouseId, string $quantity): void
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouse($productId, $warehouseId);

        if (! $stockItem || MathHelper::lessThan($stockItem->quantity, $quantity)) {
            $available = $stockItem ? $stockItem->quantity : '0';
            throw new InsufficientStockException(
                "Insufficient stock. Required: {$quantity}, Available: {$available}"
            );
        }

        // Check if allow negative stock is disabled
        if (! config('inventory.allow_negative_stock', false)) {
            $resultingQuantity = MathHelper::subtract($stockItem->quantity, $quantity);
            if (MathHelper::lessThan($resultingQuantity, '0')) {
                throw new InsufficientStockException(
                    "Operation would result in negative stock. Available: {$stockItem->quantity}"
                );
            }
        }
    }

    /**
     * Validate sufficient available (unreserved) stock.
     *
     * @throws InsufficientStockException
     */
    private function validateSufficientAvailableStock(string $productId, string $warehouseId, string $quantity): void
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouse($productId, $warehouseId);

        if (! $stockItem || MathHelper::lessThan($stockItem->available_quantity, $quantity)) {
            $available = $stockItem ? $stockItem->available_quantity : '0';
            throw new InsufficientStockException(
                "Insufficient available stock. Required: {$quantity}, Available: {$available}"
            );
        }
    }

    /**
     * Check if reorder point has been reached.
     */
    private function checkReorderPoint($stockItem, string $newAvailable): void
    {
        if ($stockItem->reorder_point !== null &&
            MathHelper::lessThan($newAvailable, $stockItem->reorder_point) &&
            MathHelper::greaterThan($stockItem->available_quantity, $stockItem->reorder_point)) {

            event(new ReorderPointReached(
                $stockItem->product_id,
                $stockItem->warehouse_id,
                $newAvailable,
                $stockItem->reorder_point
            ));
        }
    }
}
