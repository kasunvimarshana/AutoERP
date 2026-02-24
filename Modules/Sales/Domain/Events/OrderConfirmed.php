<?php

namespace Modules\Sales\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class OrderConfirmed extends DomainEvent
{
    /**
     * @param  string  $orderId
     * @param  string  $tenantId
     * @param  string|null  $customerId
     * @param  string|null  $promisedDeliveryDate  ISO 8601 date string or null
     * @param  array<int,array{product_id:string|null,description:string,qty:string,uom:string,unit_price:string|null,tax_rate:string|null}>  $lines
     */
    public function __construct(
        public readonly string  $orderId,
        public readonly string  $tenantId             = '',
        public readonly ?string $customerId           = null,
        public readonly ?string $promisedDeliveryDate = null,
        public readonly array   $lines                = [],
    ) {
        parent::__construct();
    }
}
