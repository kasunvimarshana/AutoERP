<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface BatchServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function createBatch(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function updateBatch(int|string $id, array $payload): Result;
}
