<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentGroups;

use Modules\Core\Application\Results\Result;

interface CreatePaymentGroupServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}