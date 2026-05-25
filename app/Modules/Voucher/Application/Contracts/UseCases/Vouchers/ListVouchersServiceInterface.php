<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\UseCases\Vouchers;

use Modules\Core\Application\Results\Result;

interface ListVouchersServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}