<?php

namespace Modules\POS\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class PosOrderPlaced extends DomainEvent
{
    /**
     * @param  string  $orderId
     * @param  string  $tenantId
     * @param  array<int,array{product_id:string|null,variant_id:string|null,quantity:string,location_id:string|null}>  $lines
     * @param  string|null  $customerId  Customer identifier; null for anonymous orders.
     * @param  string  $totalAmount  BCMath-safe order total for loyalty points accrual.
     */
    public function __construct(
        public readonly string  $orderId,
        public readonly string  $tenantId    = '',
        public readonly array   $lines       = [],
        public readonly ?string $customerId  = null,
        public readonly string  $totalAmount = '0',
    ) {
        parent::__construct();
    }
}
