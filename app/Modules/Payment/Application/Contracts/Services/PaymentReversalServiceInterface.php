<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PaymentReversalServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<array<string, mixed>>
     */
    public function reversePayment(int|string $paymentId, array $payload): Result;
}
