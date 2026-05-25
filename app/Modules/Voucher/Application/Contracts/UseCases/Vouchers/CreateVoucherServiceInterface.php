<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\UseCases\Vouchers;

use Modules\Core\Application\Results\Result;

interface CreateVoucherServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}