<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\Payments;

use Modules\Core\Application\Results\Result;

interface ListPaymentsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}