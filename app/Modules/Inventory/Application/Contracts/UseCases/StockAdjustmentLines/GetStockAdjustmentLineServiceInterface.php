<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines;

use Modules\Core\Application\Results\Result;

interface GetStockAdjustmentLineServiceInterface
{
    public function execute(int|string $id): Result;
}