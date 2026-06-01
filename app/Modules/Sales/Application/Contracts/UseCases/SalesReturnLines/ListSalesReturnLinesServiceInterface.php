<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesReturnLines;

use Modules\Core\Application\Results\Result;

interface ListSalesReturnLinesServiceInterface
{
    /**
     * @param  array<string, mixed>  $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
