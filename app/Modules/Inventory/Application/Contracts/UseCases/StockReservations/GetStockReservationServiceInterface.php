<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockReservations;

use Modules\Core\Application\Results\Result;

interface GetStockReservationServiceInterface
{
    public function execute(int|string $id): Result;
}