<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class ApprovalRequest
{
    public function __construct(
        private int $tenantId,
        private int $workflowConfigId,
        private string $entityType,
        private int $entityId,
        private int $requestedByUserId,
        private string $status = 'pending',
        private int $currentStepOrder = 1,
        private ?int $resolvedByUserId = null,
        private ?\DateTimeInterface $requestedAt = null,
        private ?\DateTimeInterface $resolvedAt = null,
        private ?string $comments = null,
        private int $rowVersion = 1,
        private ?int $id = null,
        private ?\DateTimeInterface $createdAt = null,
        private ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->requestedAt = $requestedAt ?? new \DateTimeImmutable;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getWorkflowConfigId(): int
    {
        return $this->workflowConfigId;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getRequestedByUserId(): int
    {
        return $this->requestedByUserId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCurrentStepOrder(): int
    {
        return $this->currentStepOrder;
    }

    public function getResolvedByUserId(): ?int
    {
        return $this->resolvedByUserId;
    }

    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function approve(int $resolvedByUserId, ?string $comments = null): void
    {
        $this->status = 'approved';
        $this->resolvedByUserId = $resolvedByUserId;
        $this->resolvedAt = new \DateTimeImmutable;
        $this->comments = $comments;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function reject(int $resolvedByUserId, ?string $comments = null): void
    {
        $this->status = 'rejected';
        $this->resolvedByUserId = $resolvedByUserId;
        $this->resolvedAt = new \DateTimeImmutable;
        $this->comments = $comments;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(?string $comments = null): void
    {
        $this->status = 'cancelled';
        $this->comments = $comments;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function advanceStep(): void
    {
        $this->currentStepOrder++;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
