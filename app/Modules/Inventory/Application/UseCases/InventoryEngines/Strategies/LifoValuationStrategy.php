<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines\Strategies;

use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\FifoValuationStrategy;
use Modules\Inventory\Application\Contracts\Strategies\ValuationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;

final class LifoValuationStrategy implements ValuationStrategyInterface
{
    public function method(): string
    {
        return InventoryValuationMethod::LIFO;
    }

    public function calculate(array $context): array
    {
        $fifo = new FifoValuationStrategy();

        return $fifo->calculate($context);
    }
}
