<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\HR\Domain\ValueObjects\PayrollRunStatus;

class PayrollRun
{
    public function __construct(
        private readonly int $tenantId,
        private \DateTimeInterface $periodStart,
        private \DateTimeInterface $periodEnd,
        private PayrollRunStatus $status,
        private ?\DateTimeInterface $processedAt,
        private ?\DateTimeInterface $approvedAt,
        private ?int $approvedBy,
        private string $totalGross,
        private string $totalDeductions,
        private string $totalNet,
        private array $metadata,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getPeriodStart(): \DateTimeInterface
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): \DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function getStatus(): PayrollRunStatus
    {
        return $this->status;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function getApprovedBy(): ?int
    {
        return $this->approvedBy;
    }

    public function getTotalGross(): string
    {
        return $this->totalGross;
    }

    public function getTotalDeductions(): string
    {
        return $this->totalDeductions;
    }

    public function getTotalNet(): string
    {
        return $this->totalNet;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function approve(int $userId): void
    {
        $this->status = PayrollRunStatus::APPROVED;
        $this->approvedBy = $userId;
        $this->approvedAt = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status = PayrollRunStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
