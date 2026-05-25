<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockLevels;

use Modules\Core\Application\Results\Result;

interface DeleteStockLevelServiceInterface
{
    public function execute(int|string $id): Result;
}