<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Contracts;

use Modules\Reservation\Application\DTOs\CreateReservationDTO;
use Modules\Reservation\Domain\Entities\Reservation;

interface ReservationServiceInterface
{
    public function getById(string $id): Reservation;

    /** @return Reservation[] */
    public function listByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Reservation[] */
    public function listByVehicle(string $tenantId, string $vehicleId): array;

    public function create(CreateReservationDTO $dto): Reservation;

    public function updateStatus(string $id, string $status): Reservation;

    public function delete(string $id): void;
}
