<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class ServiceWarrantyClaim
{
    private const VALID_STATUSES = ['draft', 'submitted', 'approved', 'rejected', 'settled'];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $serviceWorkOrderId,
        private readonly string $warrantyProvider,
        private ?int $orgUnitId = null,
        private ?int $supplierId = null,
        private ?string $claimNumber = null,
        private string $status = 'draft',
        private ?int $currencyId = null,
        private float $claimAmount = 0.0,
        private float $approvedAmount = 0.0,
        private float $receivedAmount = 0.0,
        private ?string $submittedAt = null,
        private ?string $resolvedAt = null,
        private ?int $journalEntryId = null,
        private ?string $notes = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {
        if (! in_array($this->status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid warranty claim status: {$this->status}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getServiceWorkOrderId(): int { return $this->serviceWorkOrderId; }
    public function getSupplierId(): ?int { return $this->supplierId; }
    public function getWarrantyProvider(): string { return $this->warrantyProvider; }
    public function getClaimNumber(): ?string { return $this->claimNumber; }
    public function getStatus(): string { return $this->status; }
    public function getCurrencyId(): ?int { return $this->currencyId; }
    public function getClaimAmount(): float { return $this->claimAmount; }
    public function getApprovedAmount(): float { return $this->approvedAmount; }
    public function getReceivedAmount(): float { return $this->receivedAmount; }
    public function getSubmittedAt(): ?string { return $this->submittedAt; }
    public function getResolvedAt(): ?string { return $this->resolvedAt; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
