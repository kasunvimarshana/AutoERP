<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\StockLevel;

interface StockLevelRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?StockLevel;

    /** @return StockLevel[] */
    public function findByProduct(string $tenantId, string $productId, ?string $variantId = null): array;

    /** @return StockLevel[] */
    public function findByWarehouse(string $tenantId, string $warehouseId): array;

    /** @return StockLevel[] */
    public function findByLocation(string $tenantId, string $locationId): array;

    /** @return StockLevel[] */
    public function findByBatch(string $tenantId, string $batchNumber): array;

    public function save(StockLevel $stockLevel): void;

    public function delete(string $tenantId, string $id): void;
}
