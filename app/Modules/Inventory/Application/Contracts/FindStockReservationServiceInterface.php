<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface FindStockReservationServiceInterface
{
    public function find(int $tenantId, int $reservationId): mixed;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed;
}
