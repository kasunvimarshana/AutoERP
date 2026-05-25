<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines;

use Modules\Core\Application\Results\Result;

interface ListPurchaseReturnLinesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}