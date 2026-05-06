<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseReturnLineData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $purchase_return_id,
        public readonly int $product_id,
        public readonly int $from_location_id,
        public readonly int $uom_id,
        public readonly string $return_qty,
        public readonly string $unit_cost,
        public readonly string $condition,
        public readonly string $disposition,
        public readonly string $restocking_fee = '0',
        public readonly ?int $original_grn_line_id = null,
        public readonly ?int $variant_id = null,
        public readonly ?int $batch_id = null,
        public readonly ?int $serial_id = null,
        public readonly ?string $quality_check_notes = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            purchase_return_id: (int) $data['purchase_return_id'],
            product_id: (int) $data['product_id'],
            from_location_id: (int) $data['from_location_id'],
            uom_id: (int) $data['uom_id'],
            return_qty: (string) $data['return_qty'],
            unit_cost: (string) $data['unit_cost'],
            condition: (string) $data['condition'],
            disposition: (string) $data['disposition'],
            restocking_fee: isset($data['restocking_fee']) ? (string) $data['restocking_fee'] : '0',
            original_grn_line_id: isset($data['original_grn_line_id']) ? (int) $data['original_grn_line_id'] : null,
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batch_id: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serial_id: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            quality_check_notes: isset($data['quality_check_notes']) ? (string) $data['quality_check_notes'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'purchase_return_id' => $this->purchase_return_id,
            'product_id' => $this->product_id,
            'from_location_id' => $this->from_location_id,
            'uom_id' => $this->uom_id,
            'return_qty' => $this->return_qty,
            'unit_cost' => $this->unit_cost,
            'condition' => $this->condition,
            'disposition' => $this->disposition,
            'restocking_fee' => $this->restocking_fee,
            'original_grn_line_id' => $this->original_grn_line_id,
            'variant_id' => $this->variant_id,
            'batch_id' => $this->batch_id,
            'serial_id' => $this->serial_id,
            'quality_check_notes' => $this->quality_check_notes,
        ];
    }
}
