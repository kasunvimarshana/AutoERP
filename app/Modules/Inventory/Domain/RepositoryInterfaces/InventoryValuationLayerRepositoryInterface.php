<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\InventoryValuationLayer;

interface InventoryValuationLayerRepositoryInterface
{
    public function findById(int $id): ?InventoryValuationLayer;
    public function findByProduct(int $tenantId, int $productId, int $warehouseId): array;
    public function findLayersForConsumption(int $tenantId, int $productId, int $warehouseId, string $method): array;
    public function create(array $data): InventoryValuationLayer;
    public function update(int $id, array $data): ?InventoryValuationLayer;
    public function getAverageCost(int $tenantId, int $productId, int $warehouseId): float;
}
