<?php

declare(strict_types=1);

namespace App\Contracts\Services;

/**
 * Stock Service Interface - handles inventory stock operations (Saga participant)
 */
interface StockServiceInterface
{
    /**
     * Reserve stock for an order (Saga step 1).
     * Compensating action: releaseReservation()
     */
    public function reserveStock(string $productId, string $warehouseId, int $quantity, string $sagaId): bool;

    /**
     * Release a stock reservation (Saga compensation).
     */
    public function releaseReservation(string $productId, string $warehouseId, int $quantity, string $sagaId): bool;

    /**
     * Confirm and deduct stock (Saga final step).
     * Compensating action: restoreStock()
     */
    public function deductStock(string $productId, string $warehouseId, int $quantity, string $sagaId): bool;

    /**
     * Restore stock (compensation for deductStock).
     */
    public function restoreStock(string $productId, string $warehouseId, int $quantity, string $sagaId): bool;

    /**
     * Check if sufficient stock is available.
     */
    public function checkAvailability(string $productId, string $warehouseId, int $quantity): bool;
}
