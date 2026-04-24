<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class UomConversion
{
    private ?int $id;

    private ?int $tenantId;

    private ?int $productId;

    private int $fromUomId;

    private int $toUomId;

    private string $factor;

    private bool $isBidirectional;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $fromUomId,
        int $toUomId,
        string $factor,
        ?int $tenantId = null,
        ?int $productId = null,
        bool $isBidirectional = true,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->fromUomId = $fromUomId;
        $this->toUomId = $toUomId;
        $this->factor = $factor;
        $this->isBidirectional = $isBidirectional;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromUomId(): int
    {
        return $this->fromUomId;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getToUomId(): int
    {
        return $this->toUomId;
    }

    public function getFactor(): string
    {
        return $this->factor;
    }

    public function isBidirectional(): bool
    {
        return $this->isBidirectional;
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

    public function update(
        int $fromUomId,
        int $toUomId,
        string $factor,
        ?int $tenantId,
        ?int $productId,
        bool $isBidirectional,
        bool $isActive,
    ): void {
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->fromUomId = $fromUomId;
        $this->toUomId = $toUomId;
        $this->factor = $factor;
        $this->isBidirectional = $isBidirectional;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
