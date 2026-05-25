<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers;

use Modules\Core\Application\Results\Result;

interface CreateRecurringVoucherServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}