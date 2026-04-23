<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class AttributeGroup
{
    private ?int $id;
    private int $tenantId;
    private string $name;
    private ?string $code;
    private ?string $description;
    private int $sortOrder;
    private bool $isActive;
    /** @var array<string,mixed>|null */
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        int $tenantId,
        string $name,
        ?string $code = null,
        ?string $description = null,
        int $sortOrder = 0,
        bool $isActive = true,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): ?string { return $this->code; }
    public function getDescription(): ?string { return $this->description; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isActive(): bool { return $this->isActive; }
    /** @return array<string,mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @param array<string,mixed>|null $metadata */
    public function update(
        string $name,
        ?string $code,
        ?string $description,
        int $sortOrder,
        bool $isActive,
        ?array $metadata,
    ): void {
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
