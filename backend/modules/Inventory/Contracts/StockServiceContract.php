<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Inventory\Models\StockLedger;
use Modules\Inventory\Models\StockLevel;

/**
 * Stock Service Contract
 *
 * Defines operations for stock management in the Inventory module.
 * Ensures consistent interface for inventory-related business logic.
 *
 * @package Modules\Inventory\Contracts
 */
interface StockServiceContract
{
    /**
     * Record a stock transaction
     *
     * @param array<string, mixed> $data
     * @return StockLedger
     * @throws \RuntimeException If insufficient stock and negative stock not allowed
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If product not found
     */
    public function recordTransaction(array $data): StockLedger;

    /**
     * Adjust stock quantity
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @param string $reason
     * @param int|null $locationId
     * @return StockLedger
     */
    public function adjust(int $productId, int $warehouseId, float $quantity, string $reason, ?int $locationId = null): StockLedger;

    /**
     * Reserve stock for order
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param int|null $locationId
     * @return StockLedger
     */
    public function reserve(int $productId, int $warehouseId, float $quantity, string $referenceType, int $referenceId, ?int $locationId = null): StockLedger;

    /**
     * Allocate reserved stock
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param int|null $locationId
     * @return StockLedger
     */
    public function allocate(int $productId, int $warehouseId, float $quantity, string $referenceType, int $referenceId, ?int $locationId = null): StockLedger;

    /**
     * Release reserved stock
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @param string $referenceType
     * @param int $referenceId
     * @param int|null $locationId
     * @return StockLedger
     */
    public function release(int $productId, int $warehouseId, float $quantity, string $referenceType, int $referenceId, ?int $locationId = null): StockLedger;

    /**
     * Transfer stock between locations
     *
     * @param int $productId
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @param float $quantity
     * @param int|null $fromLocationId
     * @param int|null $toLocationId
     * @return array<StockLedger>
     */
    public function transfer(int $productId, int $fromWarehouseId, int $toWarehouseId, float $quantity, ?int $fromLocationId = null, ?int $toLocationId = null): array;

    /**
     * Get current stock level
     *
     * @param int $productId
     * @param int $warehouseId
     * @param int|null $locationId
     * @return float
     */
    public function getStockLevel(int $productId, int $warehouseId, ?int $locationId = null): float;

    /**
     * Get available stock (excluding reserved)
     *
     * @param int $productId
     * @param int $warehouseId
     * @param int|null $locationId
     * @return float
     */
    public function getAvailableStock(int $productId, int $warehouseId, ?int $locationId = null): float;

    /**
     * Get reserved stock quantity
     *
     * @param int $productId
     * @param int $warehouseId
     * @param int|null $locationId
     * @return float
     */
    public function getReservedStock(int $productId, int $warehouseId, ?int $locationId = null): float;

    /**
     * Check if stock is sufficient for quantity
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @param int|null $locationId
     * @return bool
     */
    public function hasSufficientStock(int $productId, int $warehouseId, float $quantity, ?int $locationId = null): bool;

    /**
     * Get products with low stock
     *
     * @param int|null $warehouseId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts(?int $warehouseId = null);
}
