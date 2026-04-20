<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\StockReservation;

interface StockReservationRepositoryInterface
{
    public function create(StockReservation $reservation): StockReservation;

    public function findById(int $tenantId, int $reservationId): ?StockReservation;

    public function paginate(int $tenantId, int $perPage, int $page): mixed;

    public function delete(int $tenantId, int $reservationId): bool;

    public function deleteExpired(int $tenantId, ?string $expiresBefore = null): int;
}
