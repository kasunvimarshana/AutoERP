<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations;

use Modules\Core\Application\Results\Result;

interface CreateAdvancePaymentAllocationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}