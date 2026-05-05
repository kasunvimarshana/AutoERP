<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Application\Contracts\RentalServiceInterface;
use Modules\Rental\Application\DTOs\CreateRentalDTO;
use Modules\Rental\Application\DTOs\UpdateRentalDTO;
use Modules\Rental\Domain\Entities\Rental;
use Modules\Rental\Domain\Exceptions\InvalidRentalStatusTransitionException;
use Modules\Rental\Domain\Exceptions\RentalNotFoundException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRepositoryInterface;
use Modules\Rental\Domain\ValueObjects\RentalStatus;
use Modules\Rental\Domain\ValueObjects\RentalType;

class RentalService implements RentalServiceInterface
{
    public function __construct(
        private readonly RentalRepositoryInterface $rentalRepository,
    ) {}

    public function getById(int $id, int $tenantId): Rental
    {
        $rental = $this->rentalRepository->findById($id, $tenantId);
        if ($rental === null) {
            throw new RentalNotFoundException($id);
        }
        return $rental;
    }

    public function listByTenant(int $tenantId, array $filters = []): array
    {
        return $this->rentalRepository->findByTenant($tenantId, $filters);
    }

    public function listByCustomer(int $customerId, int $tenantId): array
    {
        return $this->rentalRepository->findByCustomer($customerId, $tenantId);
    }

    public function create(CreateRentalDTO $dto): Rental
    {
        return DB::transaction(function () use ($dto): Rental {
            $rental = new Rental(
                id: null,
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                customerId: $dto->customerId,
                vehicleId: $dto->vehicleId,
                driverId: $dto->driverId,
                rentalNumber: $dto->rentalNumber,
                rentalType: $dto->rentalType,
                status: RentalStatus::Pending,
                pickupLocation: $dto->pickupLocation,
                returnLocation: $dto->returnLocation,
                scheduledStartAt: new DateTimeImmutable($dto->scheduledStartAt),
                scheduledEndAt: new DateTimeImmutable($dto->scheduledEndAt),
                actualStartAt: null,
                actualEndAt: null,
                startOdometer: null,
                endOdometer: null,
                ratePerDay: $dto->ratePerDay,
                estimatedDays: $dto->estimatedDays,
                actualDays: null,
                subtotal: bcmul($dto->ratePerDay, $dto->estimatedDays, 6),
                discountAmount: '0.000000',
                taxAmount: '0.000000',
                totalAmount: bcmul($dto->ratePerDay, $dto->estimatedDays, 6),
                depositAmount: $dto->depositAmount,
                notes: $dto->notes,
                cancelledAt: null,
                cancellationReason: null,
                metadata: $dto->metadata,
                isActive: true,
                rowVersion: 1,
            );
            return $this->rentalRepository->save($rental);
        });
    }

    public function update(int $id, int $tenantId, UpdateRentalDTO $dto): Rental
    {
        return DB::transaction(function () use ($id, $tenantId, $dto): Rental {
            $existing = $this->getById($id, $tenantId);

            $updated = new Rental(
                id: $existing->id,
                tenantId: $existing->tenantId,
                orgUnitId: $existing->orgUnitId,
                customerId: $existing->customerId,
                vehicleId: $existing->vehicleId,
                driverId: $dto->driverId ?? $existing->driverId,
                rentalNumber: $existing->rentalNumber,
                rentalType: $existing->rentalType,
                status: $existing->status,
                pickupLocation: $dto->pickupLocation ?? $existing->pickupLocation,
                returnLocation: $dto->returnLocation ?? $existing->returnLocation,
                scheduledStartAt: $dto->scheduledStartAt !== null
                    ? new DateTimeImmutable($dto->scheduledStartAt)
                    : $existing->scheduledStartAt,
                scheduledEndAt: $dto->scheduledEndAt !== null
                    ? new DateTimeImmutable($dto->scheduledEndAt)
                    : $existing->scheduledEndAt,
                actualStartAt: $existing->actualStartAt,
                actualEndAt: $existing->actualEndAt,
                startOdometer: $existing->startOdometer,
                endOdometer: $existing->endOdometer,
                ratePerDay: $dto->ratePerDay ?? $existing->ratePerDay,
                estimatedDays: $dto->estimatedDays ?? $existing->estimatedDays,
                actualDays: $existing->actualDays,
                subtotal: $existing->subtotal,
                discountAmount: $existing->discountAmount,
                taxAmount: $existing->taxAmount,
                totalAmount: $existing->totalAmount,
                depositAmount: $dto->depositAmount ?? $existing->depositAmount,
                notes: $dto->notes ?? $existing->notes,
                cancelledAt: $existing->cancelledAt,
                cancellationReason: $existing->cancellationReason,
                metadata: $dto->metadata ?? $existing->metadata,
                isActive: $existing->isActive,
                rowVersion: $existing->rowVersion,
            );
            return $this->rentalRepository->save($updated);
        });
    }

    public function confirm(int $id, int $tenantId): Rental
    {
        return DB::transaction(function () use ($id, $tenantId): Rental {
            $rental = $this->getById($id, $tenantId);
            $this->assertTransition($rental->status, RentalStatus::Confirmed);
            $this->rentalRepository->updateStatus($id, $tenantId, RentalStatus::Confirmed);
            return $this->getById($id, $tenantId);
        });
    }

    public function start(int $id, int $tenantId, string $actualStartAt, string|null $startOdometer): Rental
    {
        return DB::transaction(function () use ($id, $tenantId, $actualStartAt, $startOdometer): Rental {
            $rental = $this->getById($id, $tenantId);
            $this->assertTransition($rental->status, RentalStatus::Active);
            $this->rentalRepository->updateStatus($id, $tenantId, RentalStatus::Active, [
                'actual_start_at' => $actualStartAt,
                'start_odometer'  => $startOdometer,
            ]);
            return $this->getById($id, $tenantId);
        });
    }

    public function complete(int $id, int $tenantId, string $actualEndAt, string|null $endOdometer): Rental
    {
        return DB::transaction(function () use ($id, $tenantId, $actualEndAt, $endOdometer): Rental {
            $rental = $this->getById($id, $tenantId);
            $this->assertTransition($rental->status, RentalStatus::Completed);
            $this->rentalRepository->updateStatus($id, $tenantId, RentalStatus::Completed, [
                'actual_end_at' => $actualEndAt,
                'end_odometer'  => $endOdometer,
            ]);
            return $this->getById($id, $tenantId);
        });
    }

    public function cancel(int $id, int $tenantId, string $reason): Rental
    {
        return DB::transaction(function () use ($id, $tenantId, $reason): Rental {
            $rental = $this->getById($id, $tenantId);
            $this->assertTransition($rental->status, RentalStatus::Cancelled);
            $this->rentalRepository->updateStatus($id, $tenantId, RentalStatus::Cancelled, [
                'cancelled_at'         => now()->toDateTimeString(),
                'cancellation_reason'  => $reason,
            ]);
            return $this->getById($id, $tenantId);
        });
    }

    public function delete(int $id, int $tenantId): void
    {
        DB::transaction(function () use ($id, $tenantId): void {
            $this->getById($id, $tenantId);
            $this->rentalRepository->delete($id, $tenantId);
        });
    }

    private function assertTransition(RentalStatus $from, RentalStatus $to): void
    {
        if (!$from->canTransitionTo($to)) {
            throw new InvalidRentalStatusTransitionException($from->value, $to->value);
        }
    }
}
