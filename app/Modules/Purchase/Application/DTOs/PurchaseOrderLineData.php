<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseOrderLineData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $purchase_order_id,
        public readonly int $product_id,
        public readonly int $uom_id,
        public readonly string $ordered_qty,
        public readonly string $unit_price,
        public readonly string $received_qty = '0',
        public readonly string $discount_pct = '0',
        public readonly ?int $variant_id = null,
        public readonly ?string $description = null,
        public readonly ?int $tax_group_id = null,
        public readonly ?int $account_id = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            purchase_order_id: (int) $data['purchase_order_id'],
            product_id: (int) $data['product_id'],
            uom_id: (int) $data['uom_id'],
            ordered_qty: (string) $data['ordered_qty'],
            unit_price: (string) $data['unit_price'],
            received_qty: isset($data['received_qty']) ? (string) $data['received_qty'] : '0',
            discount_pct: isset($data['discount_pct']) ? (string) $data['discount_pct'] : '0',
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            tax_group_id: isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            account_id: isset($data['account_id']) ? (int) $data['account_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'purchase_order_id' => $this->purchase_order_id,
            'product_id' => $this->product_id,
            'uom_id' => $this->uom_id,
            'ordered_qty' => $this->ordered_qty,
            'unit_price' => $this->unit_price,
            'received_qty' => $this->received_qty,
            'discount_pct' => $this->discount_pct,
            'variant_id' => $this->variant_id,
            'description' => $this->description,
            'tax_group_id' => $this->tax_group_id,
            'account_id' => $this->account_id,
        ];
    }
}
