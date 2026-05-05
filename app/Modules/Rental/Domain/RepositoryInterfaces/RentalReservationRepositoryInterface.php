<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalReservation;

interface RentalReservationRepositoryInterface
{
    public function create(RentalReservation $reservation): void;

    public function findById(string $id): ?RentalReservation;

    public function findByReservationNumber(string $tenantId, string $reservationNumber): ?RentalReservation;

    public function getByStatus(string $tenantId, string $status, int $page = 1, int $limit = 50): array;

    public function hasVehicleConflict(string $tenantId, string $vehicleId, \DateTime $startAt, \DateTime $endAt): bool;

    public function update(RentalReservation $reservation): void;
}
