<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface StockLedgerServiceInterface
{
    /**
     * Validate and record a stock movement while synchronizing the stock level snapshot.
     *
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function recordMovement(array $payload): Result;
}
