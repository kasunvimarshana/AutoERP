<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines;

use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\InventoryEngines\InventoryStrategyRegistryInterface;

final class ResolveValuationStrategyService
{
    public function __construct(private readonly InventoryStrategyRegistryInterface $strategyRegistry) {}

    public function execute(string $method): Result
    {
        return $this->strategyRegistry->resolveValuation($method);
    }
}
