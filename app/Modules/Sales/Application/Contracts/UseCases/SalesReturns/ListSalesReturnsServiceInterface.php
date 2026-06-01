<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesReturns;

use Modules\Core\Application\Results\Result;

interface ListSalesReturnsServiceInterface
{
    /**
     * @param  array<string, mixed>  $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
