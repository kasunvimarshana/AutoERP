<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class PerformanceCycle
{
    public function __construct(
        private readonly int $tenantId,
        private string $name,
        private \DateTimeInterface $periodStart,
        private \DateTimeInterface $periodEnd,
        private bool $isActive,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getPeriodStart(): \DateTimeInterface
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): \DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
}
