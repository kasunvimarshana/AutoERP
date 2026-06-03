<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines;

use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\InventoryAllocationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\AllocateInventoryStockServiceInterface;

final class AllocateInventoryStockService implements AllocateInventoryStockServiceInterface
{
    public function __construct(private readonly InventoryAllocationServiceInterface $inventoryAllocationService) {}

    public function execute(array $payload): Result
    {
        return $this->inventoryAllocationService->allocate($payload);
    }
}
