<?php

namespace Modules\ECommerce\Domain\Events;

class ECommerceOrderPlaced
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $tenantId,
    ) {}
}
