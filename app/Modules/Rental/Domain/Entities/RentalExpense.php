<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class RentalExpense
{
    private const VALID_TYPES = ['fuel', 'toll', 'maintenance_pass_through', 'cleaning', 'other'];
    private const VALID_STATUSES = ['draft', 'approved', 'posted', 'reimbursed', 'voided'];

    public function __construct(
        private readonly int $tenantId,
        private string $expenseType = 'other',
        private string $status = 'draft',
        private ?int $orgUnitId = null,
        private ?int $rentalBookingId = null,
        private ?int $assetId = null,
        private ?string $incurredAt = null,
        private ?int $supplierId = null,
        private ?int $employeeId = null,
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
        if (! in_array($this->expenseType, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException("Invalid expense type: {$this->expenseType}");
        }
        if (! in_array($this->status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid expense status: {$this->status}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getRentalBookingId(): ?int { return $this->rentalBookingId; }
    public function getAssetId(): ?int { return $this->assetId; }
    public function getExpenseType(): string { return $this->expenseType; }
    public function getIncurredAt(): ?string { return $this->incurredAt; }
    public function getSupplierId(): ?int { return $this->supplierId; }
    public function getEmployeeId(): ?int { return $this->employeeId; }
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
