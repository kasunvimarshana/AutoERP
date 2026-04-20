<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

class TenantSetting
{
    private ?int $id;

    private int $tenantId;

    private string $key;

    /** @var array<string, mixed>|null */
    private ?array $value;

    private string $group;

    private bool $isPublic;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param  array<string, mixed>|null  $value
     */
    public function __construct(
        int $tenantId,
        string $key,
        ?array $value = null,
        string $group = 'general',
        bool $isPublic = false,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->key = $key;
        $this->value = $value;
        $this->group = $group;
        $this->isPublic = $isPublic;
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

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getValue(): ?array
    {
        return $this->value;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
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
     * @param  array<string, mixed>|null  $value
     */
    public function update(?array $value, string $group, bool $isPublic): void
    {
        $this->value = $value;
        $this->group = $group;
        $this->isPublic = $isPublic;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
