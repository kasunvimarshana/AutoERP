<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryBatch;

interface FindInventoryBatchServiceInterface extends ReadServiceInterface
{
    public function findByProduct(int $tenantId, int $productId): Collection;
    public function findActiveBatches(int $tenantId, int $productId): Collection;
    public function findByBatchNumber(int $tenantId, string $batchNumber, int $productId): ?InventoryBatch;
}
