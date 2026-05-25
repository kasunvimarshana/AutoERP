<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentMethods;

use Modules\Core\Application\Results\Result;

interface UpdatePaymentMethodServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}