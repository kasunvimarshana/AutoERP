<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\InventoryEngines;

use Modules\Core\Application\Results\Result;

interface CalculateInventoryValuationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
