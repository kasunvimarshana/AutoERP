<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockAdjustments;

use Modules\Core\Application\Results\Result;

interface GetStockAdjustmentServiceInterface
{
    public function execute(int|string $id): Result;
}