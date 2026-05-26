<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentEngines;

use Modules\Core\Application\Results\Result;

interface SettlePaymentStatusServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $paymentId, array $payload): Result;
}
