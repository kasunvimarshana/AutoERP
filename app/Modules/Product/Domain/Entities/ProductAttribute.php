<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductAttribute
{
    /** @var array<string> */
    private const SUPPORTED_TYPES = ['text', 'select', 'number', 'boolean'];

    private ?int $id;

    private int $tenantId;

    private ?int $groupId;

    private string $name;

    private string $type;

    private bool $isRequired;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $type = 'select',
        bool $isRequired = false,
        ?int $groupId = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported attribute type: '.$type);
        }

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->groupId = $groupId;
        $this->name = $name;
        $this->type = $type;
        $this->isRequired = $isRequired;
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

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(string $name, string $type, bool $isRequired, ?int $groupId): void
    {
        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported attribute type: '.$type);
        }

        $this->name = $name;
        $this->type = $type;
        $this->isRequired = $isRequired;
        $this->groupId = $groupId;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
