<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

final class RentalReservation
{
    private string $id;
    private string $tenantId;
    private string $vehicleId;
    private string $customerId;
    private ?string $driverId;
    private string $reservationNumber;
    private \DateTime $startAt;
    private \DateTime $expectedReturnAt;
    private string $billingUnit;
    private string $baseRate;
    private string $estimatedDistance;
    private string $estimatedAmount;
    private string $status;
    private int $version;
    private ?string $notes;
    private \DateTime $createdAt;
    private ?\DateTime $updatedAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $vehicleId,
        string $customerId,
        ?string $driverId,
        string $reservationNumber,
        \DateTime $startAt,
        \DateTime $expectedReturnAt,
        string $billingUnit,
        string $baseRate,
        string $estimatedDistance,
        string $estimatedAmount,
        string $status,
        int $version = 1,
        ?string $notes = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->vehicleId = $vehicleId;
        $this->customerId = $customerId;
        $this->driverId = $driverId;
        $this->reservationNumber = $reservationNumber;
        $this->startAt = $startAt;
        $this->expectedReturnAt = $expectedReturnAt;
        $this->billingUnit = $billingUnit;
        $this->baseRate = $baseRate;
        $this->estimatedDistance = $estimatedDistance;
        $this->estimatedAmount = $estimatedAmount;
        $this->status = $status;
        $this->version = $version;
        $this->notes = $notes;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getVehicleId(): string
    {
        return $this->vehicleId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getDriverId(): ?string
    {
        return $this->driverId;
    }

    public function getReservationNumber(): string
    {
        return $this->reservationNumber;
    }

    public function getStartAt(): \DateTime
    {
        return $this->startAt;
    }

    public function getExpectedReturnAt(): \DateTime
    {
        return $this->expectedReturnAt;
    }

    public function getBillingUnit(): string
    {
        return $this->billingUnit;
    }

    public function getBaseRate(): string
    {
        return $this->baseRate;
    }

    public function getEstimatedDistance(): string
    {
        return $this->estimatedDistance;
    }

    public function getEstimatedAmount(): string
    {
        return $this->estimatedAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function confirm(): void
    {
        $this->status = 'confirmed';
        $this->version++;
        $this->updatedAt = new \DateTime();
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->version++;
        $this->updatedAt = new \DateTime();
    }
}
