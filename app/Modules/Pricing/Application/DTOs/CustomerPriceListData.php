<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

class CustomerPriceListData
{
    public function __construct(
        public readonly int $customer_id,
        public readonly int $price_list_id,
        public readonly int $priority = 0,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customer_id: (int) $data['customer_id'],
            price_list_id: (int) $data['price_list_id'],
            priority: (int) ($data['priority'] ?? 0),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
