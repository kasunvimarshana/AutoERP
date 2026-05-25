<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentGroups;

use Modules\Core\Application\Results\Result;

interface UpdatePaymentGroupServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}