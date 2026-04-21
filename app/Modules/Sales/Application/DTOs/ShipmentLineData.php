<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

class ShipmentLineData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $fromLocationId,
        public readonly int $uomId,
        public readonly ?int $shipmentId = null,
        public readonly ?int $salesOrderLineId = null,
        public readonly ?int $variantId = null,
        public readonly ?int $batchId = null,
        public readonly ?int $serialId = null,
        public readonly string $shippedQty = '0.000000',
        public readonly ?string $unitCost = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            productId: (int) $data['product_id'],
            fromLocationId: (int) $data['from_location_id'],
            uomId: (int) $data['uom_id'],
            shipmentId: isset($data['shipment_id']) ? (int) $data['shipment_id'] : null,
            salesOrderLineId: isset($data['sales_order_line_id']) ? (int) $data['sales_order_line_id'] : null,
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batchId: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serialId: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            shippedQty: isset($data['shipped_qty']) ? (string) $data['shipped_qty'] : '0.000000',
            unitCost: isset($data['unit_cost']) ? (string) $data['unit_cost'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'shipment_id' => $this->shipmentId,
            'sales_order_line_id' => $this->salesOrderLineId,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'batch_id' => $this->batchId,
            'serial_id' => $this->serialId,
            'from_location_id' => $this->fromLocationId,
            'uom_id' => $this->uomId,
            'shipped_qty' => $this->shippedQty,
            'unit_cost' => $this->unitCost,
        ];
    }
}
