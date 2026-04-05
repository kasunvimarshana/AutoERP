<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\InventoryBatch;

interface InventoryBatchRepositoryInterface
{
    public function findById(int $id): ?InventoryBatch;
    public function findByProduct(int $tenantId, int $productId, int $warehouseId): LengthAwarePaginator;
    public function findActiveBatches(int $tenantId, int $productId, int $warehouseId, string $strategy = 'fifo'): array;
    public function findByBatchNumber(int $tenantId, int $productId, string $batchNumber): ?InventoryBatch;
    public function create(array $data): InventoryBatch;
    public function update(int $id, array $data): ?InventoryBatch;
}
