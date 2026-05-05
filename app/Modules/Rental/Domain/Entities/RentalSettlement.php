<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class RentalSettlement
{
    private const VALID_PARTY_TYPES = ['driver', 'partner_supplier'];
    private const VALID_SETTLEMENT_TYPES = ['commission', 'payout', 'reimbursement', 'deduction'];
    private const VALID_STATUSES = ['draft', 'approved', 'posted', 'paid', 'voided'];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $rentalBookingId,
        private string $settlementPartyType = 'driver',
        private string $settlementType = 'commission',
        private string $status = 'draft',
        private ?int $orgUnitId = null,
        private ?int $employeeId = null,
        private ?int $supplierId = null,
        private ?int $currencyId = null,
        private float $amount = 0.0,
        private float $taxAmount = 0.0,
        private float $totalAmount = 0.0,
        private ?int $journalEntryId = null,
        private ?int $paymentId = null,
        private ?int $reversalOfId = null,
        private ?string $notes = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {
        if (! in_array($this->settlementPartyType, self::VALID_PARTY_TYPES, true)) {
            throw new InvalidArgumentException("Invalid settlement party type: {$this->settlementPartyType}");
        }
        if (! in_array($this->settlementType, self::VALID_SETTLEMENT_TYPES, true)) {
            throw new InvalidArgumentException("Invalid settlement type: {$this->settlementType}");
        }
        if (! in_array($this->status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid settlement status: {$this->status}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getRentalBookingId(): int { return $this->rentalBookingId; }
    public function getSettlementPartyType(): string { return $this->settlementPartyType; }
    public function getEmployeeId(): ?int { return $this->employeeId; }
    public function getSupplierId(): ?int { return $this->supplierId; }
    public function getSettlementType(): string { return $this->settlementType; }
    public function getCurrencyId(): ?int { return $this->currencyId; }
    public function getAmount(): float { return $this->amount; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getStatus(): string { return $this->status; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getPaymentId(): ?int { return $this->paymentId; }
    public function getReversalOfId(): ?int { return $this->reversalOfId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
