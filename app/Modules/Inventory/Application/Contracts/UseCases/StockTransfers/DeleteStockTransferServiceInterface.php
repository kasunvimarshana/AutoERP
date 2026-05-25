<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockTransfers;

use Modules\Core\Application\Results\Result;

interface DeleteStockTransferServiceInterface
{
    public function execute(int|string $id): Result;
}