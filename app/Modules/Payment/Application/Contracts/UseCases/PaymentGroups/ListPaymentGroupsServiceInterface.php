<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\PaymentGroups;

use Modules\Core\Application\Results\Result;

interface ListPaymentGroupsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}