<?php

namespace Modules\ECommerce\Domain\Events;

class ECommerceOrderConfirmed
{
    /**
     * @param  string  $orderId
     * @param  string  $tenantId
     * @param  array<int,array{inventory_product_id:string|null,qty:string,location_id:string|null}>  $lines
     *         Order lines enriched with inventory_product_id (resolved from product listing),
     *         qty (quantity ordered), and location_id (source stock location — null when not configured).
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $tenantId,
        public readonly array  $lines = [],
    ) {}
}
