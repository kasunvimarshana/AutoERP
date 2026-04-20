<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\StockReservation;

interface CreateStockReservationServiceInterface
{
    public function execute(array $data): StockReservation;
}
