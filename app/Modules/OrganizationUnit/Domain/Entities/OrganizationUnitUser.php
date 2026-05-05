<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Entities;

class OrganizationUnitUser
{
    private ?int $id;

    private int $tenantId;

    private int $organizationUnitId;

    private int $userId;

    private ?int $roleId;

    private bool $isPrimary;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $organizationUnitId,
        int $userId,
        ?int $roleId = null,
        bool $isPrimary = false,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->organizationUnitId = $organizationUnitId;
        $this->userId = $userId;
        $this->roleId = $roleId;
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

    public function getRole(): ?int
    {
        return $this->roleId;
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

    public function update(?int $roleId, bool $isPrimary): void
    {
        $this->roleId = $roleId;
        $this->isPrimary = $isPrimary;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
