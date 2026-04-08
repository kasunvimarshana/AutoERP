<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\StockLevel;

interface StockLevelServiceInterface
{
    public function getStockLevel(string $tenantId, string $id): StockLevel;

    /** @return StockLevel[] */
    public function getStockByProduct(string $tenantId, string $productId, ?string $variantId = null): array;

    /** @return StockLevel[] */
    public function getStockByWarehouse(string $tenantId, string $warehouseId): array;

    public function createStockLevel(string $tenantId, array $data): StockLevel;

    public function updateStockLevel(string $tenantId, string $id, array $data): StockLevel;

    public function adjustQuantity(string $tenantId, string $id, float $delta): StockLevel;

    public function reserveQuantity(string $tenantId, string $id, float $qty): StockLevel;

    public function releaseReservation(string $tenantId, string $id, float $qty): StockLevel;

    public function deleteStockLevel(string $tenantId, string $id): void;
}
