<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryCycleCount;

interface CreateCycleCountServiceInterface
{
    /**
     * Create a new pending cycle count for the given warehouse.
     *
     * @param int      $tenantId    Tenant scoping
     * @param int      $warehouseId Warehouse to be counted
     * @param int|null $productId   Optional: restrict count to a single product
     * @param string|null $notes    Optional notes / description
     */
    public function execute(
        int $tenantId,
        int $warehouseId,
        ?int $productId = null,
        ?string $notes = null,
    ): InventoryCycleCount;
}
