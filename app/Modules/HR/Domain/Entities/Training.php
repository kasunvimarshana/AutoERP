<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class Training
{
    private ?int $id;

    private int $tenantId;

    private string $title;

    private ?string $description;

    private ?string $trainer;

    private ?string $location;

    private string $startDate;

    private ?string $endDate;

    private ?int $maxParticipants;

    private string $status;

    private Metadata $metadata;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $title,
        string $startDate,
        ?string $description = null,
        ?string $trainer = null,
        ?string $location = null,
        ?string $endDate = null,
        ?int $maxParticipants = null,
        string $status = 'scheduled',
        ?Metadata $metadata = null,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->title           = $title;
        $this->startDate       = $startDate;
        $this->description     = $description;
        $this->trainer         = $trainer;
        $this->location        = $location;
        $this->endDate         = $endDate;
        $this->maxParticipants = $maxParticipants;
        $this->status          = $status;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->isActive        = $isActive;
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function start(): void
    {
        $this->status    = 'in_progress';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function complete(): void
    {
        $this->status    = 'completed';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = 'cancelled';
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        string $title,
        string $startDate,
        ?string $description,
        ?string $trainer,
        ?string $location,
        ?string $endDate,
        ?int $maxParticipants
    ): void {
        $this->title           = $title;
        $this->startDate       = $startDate;
        $this->description     = $description;
        $this->trainer         = $trainer;
        $this->location        = $location;
        $this->endDate         = $endDate;
        $this->maxParticipants = $maxParticipants;
        $this->updatedAt       = new \DateTimeImmutable;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTrainer(): ?string
    {
        return $this->trainer;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
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
