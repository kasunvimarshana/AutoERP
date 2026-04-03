<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Entities;

class UomCategory
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    private string $code;

    private ?string $description;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $code,
        ?string $description = null,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id          = $id;
        $this->tenantId    = $tenantId;
        $this->name        = $name;
        $this->code        = $code;
        $this->description = $description;
        $this->isActive    = $isActive;
        $this->createdAt   = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt   = $updatedAt ?? new \DateTimeImmutable;
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

    public function updateDetails(
        string $name,
        string $code,
        ?string $description,
        bool $isActive
    ): void {
        $this->name        = $name;
        $this->code        = $code;
        $this->description = $description;
        $this->isActive    = $isActive;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
