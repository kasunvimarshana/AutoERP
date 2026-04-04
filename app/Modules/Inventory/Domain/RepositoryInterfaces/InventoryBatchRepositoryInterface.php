<?php
namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\InventoryBatch;

interface InventoryBatchRepositoryInterface
{
    public function findById(int $id): ?InventoryBatch;
    public function findByNumber(int $tenantId, int $productId, string $batchNumber): ?InventoryBatch;
    public function findByProduct(int $productId, int $tenantId): array;
    public function create(array $data): InventoryBatch;
    public function update(InventoryBatch $batch, array $data): InventoryBatch;
}
