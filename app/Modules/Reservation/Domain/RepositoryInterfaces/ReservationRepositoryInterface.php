<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\RepositoryInterfaces;

use Modules\Reservation\Domain\Entities\Reservation;

interface ReservationRepositoryInterface
{
    public function findById(string $id): ?Reservation;

    /** @return Reservation[] */
    public function findByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Reservation[] */
    public function findByVehicle(string $tenantId, string $vehicleId): array;

    public function save(Reservation $reservation): Reservation;

    public function updateStatus(string $id, string $status): Reservation;

    public function delete(string $id): void;
}
