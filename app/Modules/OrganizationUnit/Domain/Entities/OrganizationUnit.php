<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Entities;

class OrganizationUnit
{
    private ?int $id;

    private int $tenantId;

    private ?int $typeId;

    private ?int $parentId;

    private ?int $managerUserId;

    private string $name;

    private ?string $code;

    private ?string $path;

    private int $depth;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private bool $isActive;

    private ?string $description;

    private int $left;

    private int $right;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        int $tenantId,
        string $name,
        ?int $typeId = null,
        ?int $parentId = null,
        ?int $managerUserId = null,
        ?string $code = null,
        ?string $path = null,
        int $depth = 0,
        ?array $metadata = null,
        bool $isActive = true,
        ?string $description = null,
        int $left = 0,
        int $right = 0,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->typeId = $typeId;
        $this->parentId = $parentId;
        $this->managerUserId = $managerUserId;
        $this->name = $name;
        $this->code = $code;
        $this->path = $path;
        $this->depth = $depth;
        $this->metadata = $metadata;
        $this->isActive = $isActive;
        $this->description = $description;
        $this->left = $left;
        $this->right = $right;
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

    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getManagerUserId(): ?int
    {
        return $this->managerUserId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function getRight(): int
    {
        return $this->right;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function update(
        string $name,
        ?int $typeId,
        ?int $parentId,
        ?int $managerUserId,
        ?string $code,
        ?array $metadata,
        bool $isActive,
        ?string $description,
    ): void {
        $this->name = $name;
        $this->typeId = $typeId;
        $this->parentId = $parentId;
        $this->managerUserId = $managerUserId;
        $this->code = $code;
        $this->metadata = $metadata;
        $this->isActive = $isActive;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function setPath(string $path, int $depth): void
    {
        $this->path = $path;
        $this->depth = $depth;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
