<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

final class RentalTransaction
{
    private string $id;
    private string $tenantId;
    private string $agreementId;
    private \DateTime $checkedOutAt;
    private ?\DateTime $checkedInAt;
    private int $odometerOut;
    private ?int $odometerIn;
    private string $fuelLevelOut;
    private ?string $fuelLevelIn;
    private ?string $pickupLatitude;
    private ?string $pickupLongitude;
    private ?string $dropoffLatitude;
    private ?string $dropoffLongitude;
    private string $status;

    public function __construct(
        string $id,
        string $tenantId,
        string $agreementId,
        \DateTime $checkedOutAt,
        ?\DateTime $checkedInAt,
        int $odometerOut,
        ?int $odometerIn,
        string $fuelLevelOut,
        ?string $fuelLevelIn,
        ?string $pickupLatitude,
        ?string $pickupLongitude,
        ?string $dropoffLatitude,
        ?string $dropoffLongitude,
        string $status,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->agreementId = $agreementId;
        $this->checkedOutAt = $checkedOutAt;
        $this->checkedInAt = $checkedInAt;
        $this->odometerOut = $odometerOut;
        $this->odometerIn = $odometerIn;
        $this->fuelLevelOut = $fuelLevelOut;
        $this->fuelLevelIn = $fuelLevelIn;
        $this->pickupLatitude = $pickupLatitude;
        $this->pickupLongitude = $pickupLongitude;
        $this->dropoffLatitude = $dropoffLatitude;
        $this->dropoffLongitude = $dropoffLongitude;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getAgreementId(): string
    {
        return $this->agreementId;
    }

    public function getCheckedOutAt(): \DateTime
    {
        return $this->checkedOutAt;
    }

    public function getCheckedInAt(): ?\DateTime
    {
        return $this->checkedInAt;
    }

    public function getOdometerOut(): int
    {
        return $this->odometerOut;
    }

    public function getOdometerIn(): ?int
    {
        return $this->odometerIn;
    }

    public function getFuelLevelOut(): string
    {
        return $this->fuelLevelOut;
    }

    public function getFuelLevelIn(): ?string
    {
        return $this->fuelLevelIn;
    }

    public function getPickupLatitude(): ?string
    {
        return $this->pickupLatitude;
    }

    public function getPickupLongitude(): ?string
    {
        return $this->pickupLongitude;
    }

    public function getDropoffLatitude(): ?string
    {
        return $this->dropoffLatitude;
    }

    public function getDropoffLongitude(): ?string
    {
        return $this->dropoffLongitude;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function checkIn(
        \DateTime $checkedInAt,
        int $odometerIn,
        string $fuelLevelIn,
        ?string $dropoffLatitude,
        ?string $dropoffLongitude,
    ): void {
        $this->checkedInAt = $checkedInAt;
        $this->odometerIn = $odometerIn;
        $this->fuelLevelIn = $fuelLevelIn;
        $this->dropoffLatitude = $dropoffLatitude;
        $this->dropoffLongitude = $dropoffLongitude;
        $this->status = 'closed';
    }
}
