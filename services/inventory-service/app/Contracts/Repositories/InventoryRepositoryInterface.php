<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Domain\Inventory\Models\InventoryItem;

/**
 * Inventory Repository Interface
 */
interface InventoryRepositoryInterface extends BaseRepositoryInterface
{
    public function findByProductAndWarehouse(string $productId, string $warehouseId): ?InventoryItem;
    public function reserveStock(string $productId, string $warehouseId, int $quantity): bool;
    public function releaseReservation(string $productId, string $warehouseId, int $quantity): bool;
    public function deductStock(string $productId, string $warehouseId, int $quantity): bool;
    public function getLowStockItems(string $tenantId, int $threshold = 10): \Illuminate\Database\Eloquent\Collection;
}
