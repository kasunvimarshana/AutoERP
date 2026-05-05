<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class ServiceReturn
{
    private const VALID_STATUSES = ['draft', 'approved', 'completed', 'cancelled'];
    private const VALID_RETURN_TYPES = ['inventory_return', 'customer_refund', 'supplier_return'];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $serviceWorkOrderId,
        private readonly string $returnNumber,
        private string $returnType = 'inventory_return',
        private string $status = 'draft',
        private ?int $orgUnitId = null,
        private ?string $reasonCode = null,
        private ?int $processedBy = null,
        private ?string $processedAt = null,
        private ?int $currencyId = null,
        private float $totalAmount = 0.0,
        private ?int $journalEntryId = null,
        private ?int $paymentId = null,
        private ?string $notes = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {
        if (! in_array($this->returnType, self::VALID_RETURN_TYPES, true)) {
            throw new InvalidArgumentException("Invalid return type: {$this->returnType}");
        }
        if (! in_array($this->status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid return status: {$this->status}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getServiceWorkOrderId(): int { return $this->serviceWorkOrderId; }
    public function getReturnNumber(): string { return $this->returnNumber; }
    public function getReturnType(): string { return $this->returnType; }
    public function getStatus(): string { return $this->status; }
    public function getReasonCode(): ?string { return $this->reasonCode; }
    public function getProcessedBy(): ?int { return $this->processedBy; }
    public function getProcessedAt(): ?string { return $this->processedAt; }
    public function getCurrencyId(): ?int { return $this->currencyId; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getPaymentId(): ?int { return $this->paymentId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
