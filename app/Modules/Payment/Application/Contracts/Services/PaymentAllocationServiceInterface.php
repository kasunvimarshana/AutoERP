<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PaymentAllocationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function createAllocation(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<array<string, mixed>>
     */
    public function previewAllocation(int|string $paymentId, array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function updateAllocation(int|string $id, array $payload): Result;
}
