<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers;

use Modules\Core\Application\Results\Result;

interface UpdateRecurringVoucherServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}