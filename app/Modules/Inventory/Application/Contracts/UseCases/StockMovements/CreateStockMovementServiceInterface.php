<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockMovements;

use Modules\Core\Application\Results\Result;

interface CreateStockMovementServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}