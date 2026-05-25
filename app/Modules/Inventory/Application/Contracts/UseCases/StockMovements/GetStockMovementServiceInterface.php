<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockMovements;

use Modules\Core\Application\Results\Result;

interface GetStockMovementServiceInterface
{
    public function execute(int|string $id): Result;
}