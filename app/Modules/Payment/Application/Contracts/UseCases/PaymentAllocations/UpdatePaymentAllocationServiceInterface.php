<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentAllocations;

use Modules\Core\Application\Results\Result;

interface UpdatePaymentAllocationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}