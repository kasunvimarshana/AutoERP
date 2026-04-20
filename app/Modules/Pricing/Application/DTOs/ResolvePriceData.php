<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

class ResolvePriceData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $type,
        public readonly int $product_id,
        public readonly int $uom_id,
        public readonly string $quantity = '1.000000',
        public readonly int $currency_id,
        public readonly ?int $variant_id = null,
        public readonly ?int $customer_id = null,
        public readonly ?int $supplier_id = null,
        public readonly ?string $price_date = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            type: (string) $data['type'],
            product_id: (int) $data['product_id'],
            uom_id: (int) $data['uom_id'],
            quantity: isset($data['quantity']) ? (string) $data['quantity'] : '1.000000',
            currency_id: (int) $data['currency_id'],
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            supplier_id: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            price_date: isset($data['price_date']) ? (string) $data['price_date'] : null,
        );
    }
}
