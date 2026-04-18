<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class FiscalYear
{
    public function __construct(
        private int $tenantId,
        private string $name,
        private \DateTimeInterface $startDate,
        private \DateTimeInterface $endDate,
        private string $status = 'open',
        private ?int $id = null,
        private ?\DateTimeInterface $createdAt = null,
        private ?\DateTimeInterface $updatedAt = null,
    ) {
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function update(
        string $name,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $status,
    ): void {
        $this->name = $name;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
