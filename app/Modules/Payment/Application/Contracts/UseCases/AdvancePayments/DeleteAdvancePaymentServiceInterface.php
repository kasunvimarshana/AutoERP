<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\AdvancePayments;

use Modules\Core\Application\Results\Result;

interface DeleteAdvancePaymentServiceInterface
{
    public function execute(int|string $id): Result;
}