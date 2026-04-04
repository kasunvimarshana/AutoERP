<?php
namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\InventoryValuationLayer;

interface InventoryValuationLayerRepositoryInterface
{
    public function findById(int $id): ?InventoryValuationLayer;
    public function findByProduct(int $productId, int $warehouseId, string $valuationMethod): array;
    /** Return layers with remaining_quantity > 0, ordered for FIFO (asc) or LIFO (desc) consumption. */
    public function findByProductOrdered(int $productId, int $warehouseId, string $direction = 'asc'): array;
    public function create(array $data): InventoryValuationLayer;
    public function update(InventoryValuationLayer $layer, array $data): InventoryValuationLayer;
    public function save(InventoryValuationLayer $layer): InventoryValuationLayer;
}
