<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface SerialServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function createSerial(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function updateSerial(int|string $id, array $payload): Result;
}
