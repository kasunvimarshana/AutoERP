<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface BatchLotRepositoryInterface extends RepositoryInterface
{
    public function findByBatchNumber(string $productId, string $batchNumber): mixed;

    /**
     * Return available batch/lot records (quantity > 0) for allocation,
     * scoped to product, warehouse, and optional variant.
     */
    public function findAvailableForAllocation(
        string $productId,
        string $warehouseId,
        ?string $variantId = null,
    ): \Illuminate\Support\Collection;
}
