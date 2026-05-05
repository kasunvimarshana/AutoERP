<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Entities;

use Modules\Vehicle\Domain\Exceptions\VehicleStateTransitionException;

class Vehicle
{
    private const RENTAL_STATUSES = ['available', 'reserved', 'rented', 'blocked'];

    private const SERVICE_STATUSES = ['none', 'in_maintenance', 'under_repair', 'awaiting_parts', 'quality_check', 'ready_for_pickup', 'returned_to_fleet'];

    public function __construct(
        private readonly int $tenantId,
        private readonly string $ownershipType,
        private readonly string $make,
        private readonly string $model,
        private string $rentalStatus,
        private string $serviceStatus,
        private readonly ?int $id = null,
        private readonly ?string $vin = null,
        private readonly ?string $registrationNumber = null,
        private readonly ?string $chassisNumber = null,
        private readonly ?string $nextMaintenanceDueAt = null,
    ) {
        $this->assertRentalStatus($this->rentalStatus);
        $this->assertServiceStatus($this->serviceStatus);

        if ($this->rentalStatus === 'rented' && $this->serviceStatus !== 'none') {
            throw VehicleStateTransitionException::cannotRentWhileInService($this->serviceStatus);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getOwnershipType(): string
    {
        return $this->ownershipType;
    }

    public function getMake(): string
    {
        return $this->make;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getRentalStatus(): string
    {
        return $this->rentalStatus;
    }

    public function getServiceStatus(): string
    {
        return $this->serviceStatus;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function getChassisNumber(): ?string
    {
        return $this->chassisNumber;
    }

    public function getNextMaintenanceDueAt(): ?string
    {
        return $this->nextMaintenanceDueAt;
    }

    public function markRentalStatus(string $status): void
    {
        $this->assertRentalStatus($status);

        if ($status === 'rented' && $this->serviceStatus !== 'none') {
            throw VehicleStateTransitionException::cannotRentWhileInService($this->serviceStatus);
        }

        $this->rentalStatus = $status;
    }

    public function markServiceStatus(string $status): void
    {
        $this->assertServiceStatus($status);

        if ($status !== 'none' && $this->rentalStatus === 'rented') {
            throw VehicleStateTransitionException::cannotScheduleServiceWhileRented($this->rentalStatus);
        }

        $this->serviceStatus = $status;
    }

    public function canBeRented(): bool
    {
        return $this->serviceStatus === 'none' && in_array($this->rentalStatus, ['available', 'reserved'], true);
    }

    public function canScheduleService(): bool
    {
        return $this->rentalStatus !== 'rented';
    }

    private function assertRentalStatus(string $status): void
    {
        if (! in_array($status, self::RENTAL_STATUSES, true)) {
            throw VehicleStateTransitionException::invalidStatus('rental', $status);
        }
    }

    private function assertServiceStatus(string $status): void
    {
        if (! in_array($status, self::SERVICE_STATUSES, true)) {
            throw VehicleStateTransitionException::invalidStatus('service', $status);
        }
    }
}
