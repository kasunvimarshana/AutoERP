<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentGroups;

use Modules\Core\Application\Results\Result;

interface GetPaymentGroupServiceInterface
{
    public function execute(int|string $id): Result;
}