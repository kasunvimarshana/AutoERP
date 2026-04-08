<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface InventoryServiceInterface extends ServiceInterface
{
    /**
     * Record a stock movement and update inventory_items.
     * Throws InsufficientStockException on outbound movements without sufficient stock.
     */
    public function recordMovement(array $data): mixed;

    /**
     * Reserve stock for a specific reference (e.g. order line).
     */
    public function reserveStock(string $productId, string $warehouseId, float $quantity, string $referenceType, string $referenceId): mixed;

    /**
     * Release a previously created reservation.
     */
    public function releaseReservation(string $reservationId): void;

    /**
     * Get current stock levels for a product across all warehouses.
     */
    public function getStockLevels(string $productId): \Illuminate\Support\Collection;
}
