<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Rental\Application\Contracts\CreateRentalReservationServiceInterface;
use Modules\Rental\Application\DTOs\CreateRentalReservationDTO;
use Modules\Rental\Domain\Entities\RentalReservation;
use Modules\Rental\Domain\RepositoryInterfaces\RentalReservationRepositoryInterface;

class CreateRentalReservationService implements CreateRentalReservationServiceInterface
{
    public function __construct(private readonly RentalReservationRepositoryInterface $reservations) {}

    public function execute(CreateRentalReservationDTO $dto): RentalReservation
    {
        if ($this->reservations->hasVehicleConflict($dto->tenantId, $dto->vehicleId, $dto->startAt, $dto->expectedReturnAt)) {
            throw new \RuntimeException('Vehicle already reserved in the requested period.');
        }

        $hours = max(1.0, ($dto->expectedReturnAt->getTimestamp() - $dto->startAt->getTimestamp()) / 3600);
        $estimatedAmount = bcmul($dto->baseRate, (string) $hours, 6);

        $reservation = new RentalReservation(
            id: (string) Str::uuid(),
            tenantId: $dto->tenantId,
            vehicleId: $dto->vehicleId,
            customerId: $dto->customerId,
            driverId: $dto->driverId,
            reservationNumber: 'RSV-' . strtoupper(Str::random(10)),
            startAt: $dto->startAt,
            expectedReturnAt: $dto->expectedReturnAt,
            billingUnit: $dto->billingUnit,
            baseRate: $dto->baseRate,
            estimatedDistance: $dto->estimatedDistance,
            estimatedAmount: $estimatedAmount,
            status: 'draft',
            notes: $dto->notes,
        );

        DB::transaction(function () use ($reservation): void {
            $this->reservations->create($reservation);
        });

        return $reservation;
    }
}
