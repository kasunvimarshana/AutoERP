<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Reservation\Application\Contracts\ReservationServiceInterface;
use Modules\Reservation\Application\DTOs\CreateReservationDTO;
use Modules\Reservation\Domain\Entities\Reservation;
use Modules\Reservation\Domain\Exceptions\ReservationNotFoundException;
use Modules\Reservation\Domain\RepositoryInterfaces\ReservationRepositoryInterface;
use Modules\Reservation\Domain\ValueObjects\ReservationStatus;
use Ramsey\Uuid\Uuid;

class ReservationService implements ReservationServiceInterface
{
    public function __construct(private readonly ReservationRepositoryInterface $repository)
    {
    }

    public function getById(string $id): Reservation
    {
        $reservation = $this->repository->findById($id);
        if ($reservation === null) {
            throw new ReservationNotFoundException($id);
        }

        return $reservation;
    }

    public function listByTenant(string $tenantId, string $orgUnitId): array
    {
        return $this->repository->findByTenant($tenantId, $orgUnitId);
    }

    public function listByVehicle(string $tenantId, string $vehicleId): array
    {
        return $this->repository->findByVehicle($tenantId, $vehicleId);
    }

    public function create(CreateReservationDTO $dto): Reservation
    {
        return DB::transaction(function () use ($dto): Reservation {
            $now = new DateTimeImmutable();

            $reservation = new Reservation(
                id: Uuid::uuid4()->toString(),
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                rowVersion: 1,
                reservationNumber: $dto->reservationNumber,
                vehicleId: $dto->vehicleId,
                customerId: $dto->customerId,
                reservedFrom: $dto->reservedFrom,
                reservedTo: $dto->reservedTo,
                status: ReservationStatus::Pending,
                estimatedAmount: $dto->estimatedAmount,
                currency: $dto->currency,
                notes: $dto->notes,
                metadata: $dto->metadata,
                isActive: true,
                createdAt: $now,
                updatedAt: $now,
            );

            return $this->repository->save($reservation);
        });
    }

    public function updateStatus(string $id, string $status): Reservation
    {
        return DB::transaction(function () use ($id, $status): Reservation {
            $this->getById($id);

            return $this->repository->updateStatus($id, $status);
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->getById($id);
            $this->repository->delete($id);
        });
    }
}
