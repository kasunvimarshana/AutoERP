<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class Attribute
{
    /** @var array<string> */
    private const SUPPORTED_TYPES = ['text', 'select', 'number', 'boolean'];

    private ?int $id;
    private int $tenantId;
    private ?int $groupId;
    private string $name;
    private string $type;
    private bool $isRequired;
    private ?string $code;
    private ?string $description;
    private int $sortOrder;
    private bool $isActive;
    private bool $isFilterable;
    /** @var array<string,mixed>|null */
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        int $tenantId,
        string $name,
        string $type = 'select',
        bool $isRequired = false,
        ?int $groupId = null,
        ?string $code = null,
        ?string $description = null,
        int $sortOrder = 0,
        bool $isActive = true,
        bool $isFilterable = false,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported attribute type.');
        }
        $this->tenantId = $tenantId;
        $this->groupId = $groupId;
        $this->name = $name;
        $this->type = $type;
        $this->isRequired = $isRequired;
        $this->code = $code;
        $this->description = $description;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
        $this->isFilterable = $isFilterable;
        $this->metadata = $metadata;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getGroupId(): ?int { return $this->groupId; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function isRequired(): bool { return $this->isRequired; }
    public function getCode(): ?string { return $this->code; }
    public function getDescription(): ?string { return $this->description; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isActive(): bool { return $this->isActive; }
    public function isFilterable(): bool { return $this->isFilterable; }
    /** @return array<string,mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @param array<string,mixed>|null $metadata */
    public function update(
        string $name,
        string $type,
        bool $isRequired,
        ?int $groupId,
        ?string $code,
        ?string $description,
        int $sortOrder,
        bool $isActive,
        bool $isFilterable,
        ?array $metadata,
    ): void {
        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported attribute type.');
        }
        $this->name = $name;
        $this->type = $type;
        $this->isRequired = $isRequired;
        $this->groupId = $groupId;
        $this->code = $code;
        $this->description = $description;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
        $this->isFilterable = $isFilterable;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
