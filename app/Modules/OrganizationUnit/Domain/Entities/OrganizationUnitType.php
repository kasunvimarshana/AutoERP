<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Entities;

class OrganizationUnitType
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    private int $level;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        int $level = 0,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->level = $level;
        $this->isActive = $isActive;
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

    public function getLevel(): int
    {
        return $this->level;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(string $name, int $level, bool $isActive): void
    {
        $this->name = $name;
        $this->level = $level;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
