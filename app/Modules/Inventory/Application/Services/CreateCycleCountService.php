<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class CreateCycleCountService implements CreateCycleCountServiceInterface
{
    public function __construct(
        private readonly InventoryCycleCountRepositoryInterface $cycleRepo,
    ) {}

    public function execute(
        int $tenantId,
        int $warehouseId,
        ?int $productId = null,
        ?string $notes = null,
    ): InventoryCycleCount {
        if ($warehouseId <= 0) {
            throw new \InvalidArgumentException("Warehouse ID must be a positive integer.");
        }

        return $this->cycleRepo->create([
            'tenant_id'    => $tenantId,
            'warehouse_id' => $warehouseId,
            'product_id'   => $productId,
            'status'       => 'pending',
            'notes'        => $notes,
        ]);
    }
}
