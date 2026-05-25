<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\Payments;

use Modules\Core\Application\Results\Result;

interface CreatePaymentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}