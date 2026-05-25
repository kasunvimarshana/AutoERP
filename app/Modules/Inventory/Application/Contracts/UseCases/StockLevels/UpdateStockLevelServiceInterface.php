<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockLevels;

use Modules\Core\Application\Results\Result;

interface UpdateStockLevelServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}