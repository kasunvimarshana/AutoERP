<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PaymentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function createPayment(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function updatePayment(int|string $id, array $payload): Result;
}
