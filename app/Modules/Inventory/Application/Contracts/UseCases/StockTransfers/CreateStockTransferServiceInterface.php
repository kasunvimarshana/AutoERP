<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockTransfers;

use Modules\Core\Application\Results\Result;

interface CreateStockTransferServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}