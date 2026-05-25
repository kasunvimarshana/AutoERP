<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations;

use Modules\Core\Application\Results\Result;

interface DeleteAdvancePaymentAllocationServiceInterface
{
    public function execute(int|string $id): Result;
}