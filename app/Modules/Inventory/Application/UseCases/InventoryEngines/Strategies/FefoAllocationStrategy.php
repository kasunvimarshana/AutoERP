<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines\Strategies;

use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\FifoAllocationStrategy;
use Modules\Inventory\Application\Contracts\Strategies\AllocationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryAllocationMethod;

final class FefoAllocationStrategy implements AllocationStrategyInterface
{
    public function method(): string
    {
        return InventoryAllocationMethod::FEFO;
    }

    public function allocate(array $context): array
    {
        $fifo = new FifoAllocationStrategy();

        return $fifo->allocate($context);
    }
}
