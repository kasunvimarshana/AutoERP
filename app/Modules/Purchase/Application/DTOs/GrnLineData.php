<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class GrnLineData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $grn_header_id,
        public readonly int $product_id,
        public readonly int $location_id,
        public readonly int $uom_id,
        public readonly string $received_qty,
        public readonly string $unit_cost,
        public readonly string $expected_qty = '0',
        public readonly string $rejected_qty = '0',
        public readonly ?int $purchase_order_line_id = null,
        public readonly ?int $variant_id = null,
        public readonly ?int $batch_id = null,
        public readonly ?int $serial_id = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            grn_header_id: (int) $data['grn_header_id'],
            product_id: (int) $data['product_id'],
            location_id: (int) $data['location_id'],
            uom_id: (int) $data['uom_id'],
            received_qty: (string) $data['received_qty'],
            unit_cost: (string) $data['unit_cost'],
            expected_qty: isset($data['expected_qty']) ? (string) $data['expected_qty'] : '0',
            rejected_qty: isset($data['rejected_qty']) ? (string) $data['rejected_qty'] : '0',
            purchase_order_line_id: isset($data['purchase_order_line_id']) ? (int) $data['purchase_order_line_id'] : null,
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batch_id: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serial_id: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'grn_header_id' => $this->grn_header_id,
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            'uom_id' => $this->uom_id,
            'received_qty' => $this->received_qty,
            'unit_cost' => $this->unit_cost,
            'expected_qty' => $this->expected_qty,
            'rejected_qty' => $this->rejected_qty,
            'purchase_order_line_id' => $this->purchase_order_line_id,
            'variant_id' => $this->variant_id,
            'batch_id' => $this->batch_id,
            'serial_id' => $this->serial_id,
        ];
    }
}
