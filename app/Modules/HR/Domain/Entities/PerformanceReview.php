<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class PerformanceReview
{
    private ?int $id;

    private int $tenantId;

    private int $employeeId;

    private int $reviewerId;

    private string $reviewPeriodStart;

    private string $reviewPeriodEnd;

    private float $rating;

    private ?string $comments;

    private ?string $goals;

    private ?string $achievements;

    private string $status;

    private Metadata $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $employeeId,
        int $reviewerId,
        string $reviewPeriodStart,
        string $reviewPeriodEnd,
        float $rating,
        ?string $comments = null,
        ?string $goals = null,
        ?string $achievements = null,
        string $status = 'draft',
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id                = $id;
        $this->tenantId          = $tenantId;
        $this->employeeId        = $employeeId;
        $this->reviewerId        = $reviewerId;
        $this->reviewPeriodStart = $reviewPeriodStart;
        $this->reviewPeriodEnd   = $reviewPeriodEnd;
        $this->rating            = $rating;
        $this->comments          = $comments;
        $this->goals             = $goals;
        $this->achievements      = $achievements;
        $this->status            = $status;
        $this->metadata          = $metadata ?? new Metadata([]);
        $this->createdAt         = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt         = $updatedAt ?? new \DateTimeImmutable;
    }

    public function submit(): void
    {
        $this->status    = 'submitted';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function acknowledge(): void
    {
        $this->status    = 'acknowledged';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        string $reviewPeriodStart,
        string $reviewPeriodEnd,
        float $rating,
        ?string $comments,
        ?string $goals,
        ?string $achievements
    ): void {
        $this->reviewPeriodStart = $reviewPeriodStart;
        $this->reviewPeriodEnd   = $reviewPeriodEnd;
        $this->rating            = $rating;
        $this->comments          = $comments;
        $this->goals             = $goals;
        $this->achievements      = $achievements;
        $this->updatedAt         = new \DateTimeImmutable;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

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

    public function getReviewerId(): int
    {
        return $this->reviewerId;
    }

    public function getReviewPeriodStart(): string
    {
        return $this->reviewPeriodStart;
    }

    public function getReviewPeriodEnd(): string
    {
        return $this->reviewPeriodEnd;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getGoals(): ?string
    {
        return $this->goals;
    }

    public function getAchievements(): ?string
    {
        return $this->achievements;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMetadata(): Metadata
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
}
