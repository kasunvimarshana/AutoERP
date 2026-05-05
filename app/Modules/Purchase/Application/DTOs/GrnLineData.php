<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class GrnLineData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $grnHeaderId,
        public readonly int $productId,
        public readonly int $locationId,
        public readonly int $uomId,
        public readonly string $receivedQty,
        public readonly string $unitCost,
        public readonly string $expectedQty = '0',
        public readonly string $rejectedQty = '0',
        public readonly ?int $purchaseOrderLineId = null,
        public readonly ?int $variantId = null,
        public readonly ?int $batchId = null,
        public readonly ?int $serialId = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            grnHeaderId: (int) $data['grn_header_id'],
            productId: (int) $data['product_id'],
            locationId: (int) $data['location_id'],
            uomId: (int) $data['uom_id'],
            receivedQty: (string) $data['received_qty'],
            unitCost: (string) $data['unit_cost'],
            expectedQty: isset($data['expected_qty']) ? (string) $data['expected_qty'] : '0',
            rejectedQty: isset($data['rejected_qty']) ? (string) $data['rejected_qty'] : '0',
            purchaseOrderLineId: isset($data['purchase_order_line_id']) ? (int) $data['purchase_order_line_id'] : null,
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batchId: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serialId: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'grn_header_id' => $this->grnHeaderId,
            'product_id' => $this->productId,
            'location_id' => $this->locationId,
            'uom_id' => $this->uomId,
            'received_qty' => $this->receivedQty,
            'unit_cost' => $this->unitCost,
            'expected_qty' => $this->expectedQty,
            'rejected_qty' => $this->rejectedQty,
            'purchase_order_line_id' => $this->purchaseOrderLineId,
            'variant_id' => $this->variantId,
            'batch_id' => $this->batchId,
            'serial_id' => $this->serialId,
        ];
    }
}
