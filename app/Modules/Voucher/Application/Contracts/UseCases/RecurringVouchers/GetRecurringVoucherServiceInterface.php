<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers;

use Modules\Core\Application\Results\Result;

interface GetRecurringVoucherServiceInterface
{
    public function execute(int|string $id): Result;
}