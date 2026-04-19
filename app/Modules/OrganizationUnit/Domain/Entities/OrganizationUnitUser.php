<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Entities;

class OrganizationUnitUser
{
    private ?int $id;

    private int $tenantId;

    private int $organizationUnitId;

    private int $userId;

    private ?string $role;

    private bool $isPrimary;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $organizationUnitId,
        int $userId,
        ?string $role = null,
        bool $isPrimary = false,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->organizationUnitId = $organizationUnitId;
        $this->userId = $userId;
        $this->role = $role;
        $this->isPrimary = $isPrimary;
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

    public function getOrganizationUnitId(): int
    {
        return $this->organizationUnitId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(?string $role, bool $isPrimary): void
    {
        $this->role = $role;
        $this->isPrimary = $isPrimary;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
