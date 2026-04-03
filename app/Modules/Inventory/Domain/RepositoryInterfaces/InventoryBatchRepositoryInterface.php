<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryBatch;

interface InventoryBatchRepositoryInterface extends RepositoryInterface
{
    public function save(InventoryBatch $batch): InventoryBatch;

    public function findByProduct(int $tenantId, int $productId): Collection;

    public function findActiveBatches(int $tenantId, int $productId): Collection;

    public function findByBatchNumber(int $tenantId, string $batchNumber, int $productId): ?InventoryBatch;
}
