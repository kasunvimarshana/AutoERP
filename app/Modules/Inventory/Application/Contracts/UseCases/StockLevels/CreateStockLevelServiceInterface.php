<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockLevels;

use Modules\Core\Application\Results\Result;

interface CreateStockLevelServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}