<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Entities;

class UomConversion
{
    private ?int $id;

    private int $tenantId;

    private int $fromUomId;

    private int $toUomId;

    private float $factor;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $fromUomId,
        int $toUomId,
        float $factor,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id        = $id;
        $this->tenantId  = $tenantId;
        $this->fromUomId = $fromUomId;
        $this->toUomId   = $toUomId;
        $this->factor    = $factor;
        $this->isActive  = $isActive;
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

    public function getFromUomId(): int
    {
        return $this->fromUomId;
    }

    public function getToUomId(): int
    {
        return $this->toUomId;
    }

    public function getFactor(): float
    {
        return $this->factor;
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

    public function updateFactor(float $factor): void
    {
        $this->factor    = $factor;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function convert(float $qty): float
    {
        return $qty * $this->factor;
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
