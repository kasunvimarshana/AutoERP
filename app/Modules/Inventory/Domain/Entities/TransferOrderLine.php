<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class TransferOrderLine
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $productId,
        private readonly ?int $variantId,
        private readonly ?int $batchId,
        private readonly ?int $serialId,
        private readonly ?int $fromLocationId,
        private readonly ?int $toLocationId,
        private readonly int $uomId,
        private readonly string $requestedQty,
        private readonly string $shippedQty,
        private readonly string $receivedQty,
        private readonly ?string $unitCost,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function getBatchId(): ?int
    {
        return $this->batchId;
    }

    public function getSerialId(): ?int
    {
        return $this->serialId;
    }

    public function getFromLocationId(): ?int
    {
        return $this->fromLocationId;
    }

    public function getToLocationId(): ?int
    {
        return $this->toLocationId;
    }

    public function getUomId(): int
    {
        return $this->uomId;
    }

    public function getRequestedQty(): string
    {
        return $this->requestedQty;
    }

    public function getShippedQty(): string
    {
        return $this->shippedQty;
    }

    public function getReceivedQty(): string
    {
        return $this->receivedQty;
    }

    public function getUnitCost(): ?string
    {
        return $this->unitCost;
    }
}
