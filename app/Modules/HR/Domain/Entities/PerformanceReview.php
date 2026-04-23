<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\HR\Domain\ValueObjects\PerformanceRating;

class PerformanceReview
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $employeeId,
        private readonly int $cycleId,
        private readonly int $reviewerId,
        private ?PerformanceRating $overallRating,
        private array $goals,
        private string $strengths,
        private string $improvements,
        private string $reviewerComments,
        private string $employeeComments,
        private string $status,
        private ?\DateTimeInterface $acknowledgedAt,
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

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getCycleId(): int
    {
        return $this->cycleId;
    }

    public function getReviewerId(): int
    {
        return $this->reviewerId;
    }

    public function getOverallRating(): ?PerformanceRating
    {
        return $this->overallRating;
    }

    public function getGoals(): array
    {
        return $this->goals;
    }

    public function getStrengths(): string
    {
        return $this->strengths;
    }

    public function getImprovements(): string
    {
        return $this->improvements;
    }

    public function getReviewerComments(): string
    {
        return $this->reviewerComments;
    }

    public function getEmployeeComments(): string
    {
        return $this->employeeComments;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAcknowledgedAt(): ?\DateTimeInterface
    {
        return $this->acknowledgedAt;
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

    public function submit(): void
    {
        $this->status = 'submitted';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function acknowledge(): void
    {
        $this->status = 'acknowledged';
        $this->acknowledgedAt = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
