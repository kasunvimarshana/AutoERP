<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\DTOs;

class SupplierProductData
{
    public function __construct(
        public readonly int $supplier_id,
        public readonly int $product_id,
        public readonly ?int $variant_id = null,
        public readonly ?string $supplier_sku = null,
        public readonly ?int $lead_time_days = null,
        public readonly string $min_order_qty = '1.000000',
        public readonly bool $is_preferred = false,
        public readonly ?string $last_purchase_price = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supplier_id: (int) $data['supplier_id'],
            product_id: (int) $data['product_id'],
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            supplier_sku: isset($data['supplier_sku']) ? (string) $data['supplier_sku'] : null,
            lead_time_days: isset($data['lead_time_days']) ? (int) $data['lead_time_days'] : null,
            min_order_qty: isset($data['min_order_qty']) ? (string) $data['min_order_qty'] : '1.000000',
            is_preferred: (bool) ($data['is_preferred'] ?? false),
            last_purchase_price: isset($data['last_purchase_price']) ? (string) $data['last_purchase_price'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
