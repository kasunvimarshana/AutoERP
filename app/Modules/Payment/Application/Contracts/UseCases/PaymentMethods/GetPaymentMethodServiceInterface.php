<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentMethods;

use Modules\Core\Application\Results\Result;

interface GetPaymentMethodServiceInterface
{
    public function execute(int|string $id): Result;
}