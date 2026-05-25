<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\UseCases\Vouchers;

use Modules\Core\Application\Results\Result;

interface DeleteVoucherServiceInterface
{
    public function execute(int|string $id): Result;
}