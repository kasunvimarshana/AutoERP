<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface ReleaseStockReservationServiceInterface
{
    public function execute(int $tenantId, int $reservationId): bool;
}
