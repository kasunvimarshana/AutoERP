<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentGroups;

use Modules\Core\Application\Results\Result;

interface DeletePaymentGroupServiceInterface
{
    public function execute(int|string $id): Result;
}