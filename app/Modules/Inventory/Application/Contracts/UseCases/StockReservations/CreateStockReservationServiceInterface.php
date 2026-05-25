<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\StockReservations;

use Modules\Core\Application\Results\Result;

interface CreateStockReservationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}