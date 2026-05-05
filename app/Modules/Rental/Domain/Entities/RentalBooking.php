<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

class RentalBooking
{
    private ?int $id;

    public function __construct(
        private int $tenantId,
        private int $customerId,
        private string $bookingNumber,
        private string $bookingType,
        private string $fleetSource,
        private string $status = 'draft',
        private ?int $orgUnitId = null,
        private ?\DateTimeInterface $scheduledStartAt = null,
        private ?\DateTimeInterface $scheduledEndAt = null,
        private ?\DateTimeInterface $actualStartAt = null,
        private ?\DateTimeInterface $actualEndAt = null,
        private string $subtotal = '0.000000',
        private string $discountAmount = '0.000000',
        private string $taxAmount = '0.000000',
        private string $depositAmount = '0.000000',
        private string $totalAmount = '0.000000',
        private string $depositStatus = 'pending',
        private ?int $arTransactionId = null,
        private ?int $journalEntryId = null,
        private ?string $notes = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getBookingNumber(): string { return $this->bookingNumber; }
    public function getBookingType(): string { return $this->bookingType; }
    public function getFleetSource(): string { return $this->fleetSource; }
    public function getStatus(): string { return $this->status; }
    public function getScheduledStartAt(): ?\DateTimeInterface { return $this->scheduledStartAt; }
    public function getScheduledEndAt(): ?\DateTimeInterface { return $this->scheduledEndAt; }
    public function getActualStartAt(): ?\DateTimeInterface { return $this->actualStartAt; }
    public function getActualEndAt(): ?\DateTimeInterface { return $this->actualEndAt; }
    public function getSubtotal(): string { return $this->subtotal; }
    public function getDiscountAmount(): string { return $this->discountAmount; }
    public function getTaxAmount(): string { return $this->taxAmount; }
    public function getDepositAmount(): string { return $this->depositAmount; }
    public function getTotalAmount(): string { return $this->totalAmount; }
    public function getDepositStatus(): string { return $this->depositStatus; }
    public function getArTransactionId(): ?int { return $this->arTransactionId; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function confirm(): void
    {
        $this->status = 'confirmed';
        $this->rowVersion++;
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->rowVersion++;
    }

    public function update(array $fields): void
    {
        foreach ($fields as $field => $value) {
            if (property_exists($this, $field) && $field !== 'id' && $field !== 'tenantId') {
                $this->{$field} = $value;
            }
        }
        $this->rowVersion++;
    }
}
