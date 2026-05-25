<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations;

use Modules\Core\Application\Results\Result;

interface ListAdvancePaymentAllocationsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}