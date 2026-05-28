<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface WriteOffServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function createWriteOff(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function updateWriteOff(int|string $id, array $payload): Result;
}
