<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockReservations;

use Modules\Core\Application\Results\Result;

interface ListStockReservationsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}