<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\Entities;

class TaxGroup
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    private ?string $description;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        ?string $description = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = trim($name);
        $this->description = $description !== null ? trim($description) : null;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(string $name, ?string $description = null): void
    {
        $this->name = trim($name);
        $this->description = $description !== null ? trim($description) : null;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
