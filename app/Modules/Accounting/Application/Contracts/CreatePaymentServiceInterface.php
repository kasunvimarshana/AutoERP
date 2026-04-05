<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Domain\Entities\Payment;

interface CreatePaymentServiceInterface
{
    public function execute(array $data): Payment;
}
