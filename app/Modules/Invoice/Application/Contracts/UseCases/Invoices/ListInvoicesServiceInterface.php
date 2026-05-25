<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Contracts\UseCases\Invoices;

use Modules\Core\Application\Results\Result;

interface ListInvoicesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}