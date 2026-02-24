<?php

namespace Modules\Logistics\Domain\Entities;

class DeliveryLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenant_id,
        public readonly string $delivery_order_id,
        public readonly string $product_id,
        public readonly string $product_name,
        public readonly string $quantity,
        public readonly string $unit,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}
}
