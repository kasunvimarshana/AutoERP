<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockAdjustments;

use Modules\Core\Application\Results\Result;

interface CreateStockAdjustmentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}