<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\AdvancePayments;

use Modules\Core\Application\Results\Result;

interface CreateAdvancePaymentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}