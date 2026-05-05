<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Entities;

class VehicleRental
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $vehicleId,
        private readonly string $rentalNo,
        private readonly string $rentalStatus,
        private readonly string $pricingModel,
        private readonly string $grandTotal,
        private readonly ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }

    public function getRentalNo(): string
    {
        return $this->rentalNo;
    }

    public function getRentalStatus(): string
    {
        return $this->rentalStatus;
    }

    public function getPricingModel(): string
    {
        return $this->pricingModel;
    }

    public function getGrandTotal(): string
    {
        return $this->grandTotal;
    }
}
