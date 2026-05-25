<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentMethods;

use Modules\Core\Application\Results\Result;

interface CreatePaymentMethodServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}