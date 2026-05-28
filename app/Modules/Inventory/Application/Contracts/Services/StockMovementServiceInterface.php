<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface StockMovementServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function updateMovement(int|string $id, array $payload): Result;
}
