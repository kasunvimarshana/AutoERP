<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockTransferLines;

use Modules\Core\Application\Results\Result;

interface DeleteStockTransferLineServiceInterface
{
    public function execute(int|string $id): Result;
}