<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface RefundServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function refundPayment(int|string $sourcePaymentId, array $payload): Result;
}
