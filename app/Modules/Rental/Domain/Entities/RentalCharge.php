<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class RentalCharge
{
    private const VALID_TYPES = ['rental_fee', 'penalty', 'extension', 'fuel', 'cleaning', 'other'];
    private const VALID_DIRECTIONS = ['receivable', 'payable'];
    private const VALID_STATUSES = ['draft', 'posted', 'paid', 'voided'];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $rentalBookingId,
        private string $chargeType = 'rental_fee',
        private string $chargeDirection = 'receivable',
        private string $status = 'draft',
        private ?int $orgUnitId = null,
        private ?int $rentalIncidentId = null,
        private ?int $currencyId = null,
        private float $amount = 0.0,
        private float $taxAmount = 0.0,
        private float $totalAmount = 0.0,
        private ?string $dueDate = null,
        private ?int $journalEntryId = null,
        private ?int $paymentId = null,
        private ?int $reversalOfId = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {
        if (! in_array($this->chargeType, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException("Invalid charge type: {$this->chargeType}");
        }
        if (! in_array($this->chargeDirection, self::VALID_DIRECTIONS, true)) {
            throw new InvalidArgumentException("Invalid charge direction: {$this->chargeDirection}");
        }
        if (! in_array($this->status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid charge status: {$this->status}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getRentalBookingId(): int { return $this->rentalBookingId; }
    public function getRentalIncidentId(): ?int { return $this->rentalIncidentId; }
    public function getChargeType(): string { return $this->chargeType; }
    public function getChargeDirection(): string { return $this->chargeDirection; }
    public function getCurrencyId(): ?int { return $this->currencyId; }
    public function getAmount(): float { return $this->amount; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getDueDate(): ?string { return $this->dueDate; }
    public function getStatus(): string { return $this->status; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getPaymentId(): ?int { return $this->paymentId; }
    public function getReversalOfId(): ?int { return $this->reversalOfId; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
