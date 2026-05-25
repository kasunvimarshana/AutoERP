<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockTransferLines;

use Modules\Core\Application\Results\Result;

interface CreateStockTransferLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}