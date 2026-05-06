<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

class RentalBooking
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $customerId,
        private readonly string $rentalMode,
        private readonly string $ownershipModel,
        private readonly string $pickupAt,
        private readonly string $returnDueAt,
        private readonly int $currencyId,
        private readonly string $ratePlan,
        private readonly float $rateAmount,
        private ?string $status = 'draft',
        private readonly ?int $orgUnitId = null,
        private readonly ?string $bookingNumber = null,
        private readonly ?string $actualReturnAt = null,
        private readonly ?string $pickupLocation = null,
        private readonly ?string $returnLocation = null,
        private readonly float $estimatedAmount = 0.0,
        private readonly float $finalAmount = 0.0,
        private readonly float $securityDepositAmount = 0.0,
        private readonly string $securityDepositStatus = 'not_required',
        private readonly ?int $partnerSupplierId = null,
        private readonly ?string $termsAndConditions = null,
        private readonly ?string $notes = null,
        private readonly ?array $metadata = null,
        private readonly int $rowVersion = 1,
        private readonly ?int $id = null,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertRentalMode($rentalMode);
        $this->assertOwnershipModel($ownershipModel);
        $this->assertRatePlan($ratePlan);
        $this->assertStatus($status ?? 'draft');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getBookingNumber(): ?string
    {
        return $this->bookingNumber;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getRentalMode(): string
    {
        return $this->rentalMode;
    }

    public function getOwnershipModel(): string
    {
        return $this->ownershipModel;
    }

    public function getStatus(): string
    {
        return $this->status ?? 'draft';
    }

    public function getPickupAt(): string
    {
        return $this->pickupAt;
    }

    public function getReturnDueAt(): string
    {
        return $this->returnDueAt;
    }

    public function getActualReturnAt(): ?string
    {
        return $this->actualReturnAt;
    }

    public function getPickupLocation(): ?string
    {
        return $this->pickupLocation;
    }

    public function getReturnLocation(): ?string
    {
        return $this->returnLocation;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getRatePlan(): string
    {
        return $this->ratePlan;
    }

    public function getRateAmount(): float
    {
        return $this->rateAmount;
    }

    public function getEstimatedAmount(): float
    {
        return $this->estimatedAmount;
    }

    public function getFinalAmount(): float
    {
        return $this->finalAmount;
    }

    public function getSecurityDepositAmount(): float
    {
        return $this->securityDepositAmount;
    }

    public function getSecurityDepositStatus(): string
    {
        return $this->securityDepositStatus;
    }

    public function getPartnerSupplierId(): ?int
    {
        return $this->partnerSupplierId;
    }

    public function getTermsAndConditions(): ?string
    {
        return $this->termsAndConditions;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function isTransitionAllowed(string $targetStatus): bool
    {
        $allowed = [
            'draft'     => ['reserved', 'cancelled'],
            'reserved'  => ['active', 'cancelled', 'no_show'],
            'active'    => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
            'no_show'   => [],
        ];

        return in_array($targetStatus, $allowed[$this->getStatus()] ?? [], true);
    }

    private function assertRentalMode(string $mode): void
    {
        if (! in_array($mode, ['with_driver', 'without_driver'], true)) {
            throw new \InvalidArgumentException("Invalid rental mode: {$mode}");
        }
    }

    private function assertOwnershipModel(string $model): void
    {
        if (! in_array($model, ['owned_fleet', 'third_party', 'mixed'], true)) {
            throw new \InvalidArgumentException("Invalid ownership model: {$model}");
        }
    }

    private function assertRatePlan(string $plan): void
    {
        if (! in_array($plan, ['hourly', 'daily', 'weekly', 'monthly', 'custom'], true)) {
            throw new \InvalidArgumentException("Invalid rate plan: {$plan}");
        }
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, ['draft', 'reserved', 'active', 'completed', 'cancelled', 'no_show'], true)) {
            throw new \InvalidArgumentException("Invalid booking status: {$status}");
        }
    }
}
