<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class UnitOfMeasure
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    private string $symbol;

    private string $type;

    private bool $isBase;

    private int $rowVersion;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $symbol,
        string $type = 'unit',
        bool $isBase = false,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->symbol = $symbol;
        $this->type = $type;
        $this->isBase = $isBase;
        $this->rowVersion = $rowVersion;
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

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isBase(): bool
    {
        return $this->isBase;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function update(
        string $name,
        string $symbol,
        string $type,
        bool $isBase,
    ): void {
        $this->name = $name;
        $this->symbol = $symbol;
        $this->type = $type;
        $this->isBase = $isBase;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
