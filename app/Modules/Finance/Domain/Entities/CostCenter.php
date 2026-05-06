<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class CostCenter
{
    public function __construct(
        private int $tenantId,
        private string $code,
        private string $name,
        private ?int $parentId = null,
        private ?string $description = null,
        private bool $isActive = true,
        private ?string $path = null,
        private int $depth = 0,
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

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        string $code,
        string $name,
        ?int $parentId,
        ?string $description,
        bool $isActive,
    ): void {
        $this->code = $code;
        $this->name = $name;
        $this->parentId = $parentId;
        $this->description = $description;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function setHierarchy(?string $path, int $depth): void
    {
        $this->path = $path;
        $this->depth = $depth;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
