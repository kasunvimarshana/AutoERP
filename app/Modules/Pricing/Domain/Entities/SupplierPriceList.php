<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

class SupplierPriceList
{
    private ?int $id;

    private int $tenantId;

    private int $supplierId;

    private int $priceListId;

    private int $priority;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $supplierId,
        int $priceListId,
        int $priority = 0,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        if ($priority < 0) {
            throw new \InvalidArgumentException('Priority cannot be negative.');
        }

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->supplierId = $supplierId;
        $this->priceListId = $priceListId;
        $this->priority = $priority;
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

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getPriceListId(): int
    {
        return $this->priceListId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
