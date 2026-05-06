<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

class PriceListItemData
{
    public function __construct(
        public readonly int $price_list_id,
        public readonly int $product_id,
        public readonly int $uom_id,
        public readonly string $price,
        public readonly ?int $variant_id = null,
        public readonly string $min_quantity = '1.000000',
        public readonly string $discount_pct = '0.000000',
        public readonly ?string $valid_from = null,
        public readonly ?string $valid_to = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            price_list_id: (int) $data['price_list_id'],
            product_id: (int) $data['product_id'],
            uom_id: (int) $data['uom_id'],
            price: (string) $data['price'],
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            min_quantity: isset($data['min_quantity']) ? (string) $data['min_quantity'] : '1.000000',
            discount_pct: isset($data['discount_pct']) ? (string) $data['discount_pct'] : '0.000000',
            valid_from: isset($data['valid_from']) ? (string) $data['valid_from'] : null,
            valid_to: isset($data['valid_to']) ? (string) $data['valid_to'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
