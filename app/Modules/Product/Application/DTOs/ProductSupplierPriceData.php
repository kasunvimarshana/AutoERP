<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductSupplierPriceData
{
    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $product_id,
        public readonly int $supplier_id,
        public readonly int $uom_id,
        public readonly string $unit_price,
        public readonly ?int $variant_id = null,
        public readonly ?int $currency_id = null,
        public readonly string $min_order_quantity = '1',
        public readonly string $discount_percent = '0',
        public readonly int $lead_time_days = 0,
        public readonly bool $is_preferred = false,
        public readonly bool $is_active = true,
        public readonly ?string $effective_from = null,
        public readonly ?string $effective_to = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            product_id: (int) $data['product_id'],
            supplier_id: (int) $data['supplier_id'],
            uom_id: (int) $data['uom_id'],
            unit_price: (string) $data['unit_price'],
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            currency_id: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            min_order_quantity: (string) ($data['min_order_quantity'] ?? '1'),
            discount_percent: (string) ($data['discount_percent'] ?? '0'),
            lead_time_days: (int) ($data['lead_time_days'] ?? 0),
            is_preferred: (bool) ($data['is_preferred'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true),
            effective_from: isset($data['effective_from']) ? (string) $data['effective_from'] : null,
            effective_to: isset($data['effective_to']) ? (string) $data['effective_to'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'supplier_id' => $this->supplier_id,
            'currency_id' => $this->currency_id,
            'uom_id' => $this->uom_id,
            'min_order_quantity' => $this->min_order_quantity,
            'unit_price' => $this->unit_price,
            'discount_percent' => $this->discount_percent,
            'lead_time_days' => $this->lead_time_days,
            'is_preferred' => $this->is_preferred,
            'is_active' => $this->is_active,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
