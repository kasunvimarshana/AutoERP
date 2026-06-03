<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines;

use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\InventoryValuationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\CalculateInventoryValuationServiceInterface;

final class CalculateInventoryValuationService implements CalculateInventoryValuationServiceInterface
{
    public function __construct(private readonly InventoryValuationServiceInterface $inventoryValuationService) {}

    public function execute(array $payload): Result
    {
        return $this->inventoryValuationService->value($payload);
    }
}
