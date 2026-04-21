<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

class ShipmentLine
{
    private ?int $id;

    private int $tenantId;

    private ?int $shipmentId;

    private ?int $salesOrderLineId;

    private int $productId;

    private ?int $variantId;

    private ?int $batchId;

    private ?int $serialId;

    private int $fromLocationId;

    private int $uomId;

    private string $shippedQty;

    private ?string $unitCost;

    public function __construct(
        int $tenantId,
        int $productId,
        int $fromLocationId,
        int $uomId,
        ?int $shipmentId = null,
        ?int $salesOrderLineId = null,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        string $shippedQty = '0.000000',
        ?string $unitCost = null,
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->shipmentId = $shipmentId;
        $this->salesOrderLineId = $salesOrderLineId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->fromLocationId = $fromLocationId;
        $this->uomId = $uomId;
        $this->shippedQty = $shippedQty;
        $this->unitCost = $unitCost;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getShipmentId(): ?int
    {
        return $this->shipmentId;
    }

    public function getSalesOrderLineId(): ?int
    {
        return $this->salesOrderLineId;
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

    public function getFromLocationId(): int
    {
        return $this->fromLocationId;
    }

    public function getUomId(): int
    {
        return $this->uomId;
    }

    public function getShippedQty(): string
    {
        return $this->shippedQty;
    }

    public function getUnitCost(): ?string
    {
        return $this->unitCost;
    }

    public function update(
        int $productId,
        int $fromLocationId,
        int $uomId,
        ?int $salesOrderLineId = null,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        string $shippedQty = '0.000000',
        ?string $unitCost = null,
    ): void {
        $this->productId = $productId;
        $this->salesOrderLineId = $salesOrderLineId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->fromLocationId = $fromLocationId;
        $this->uomId = $uomId;
        $this->shippedQty = $shippedQty;
        $this->unitCost = $unitCost;
    }
}
