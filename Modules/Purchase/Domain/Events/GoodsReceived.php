<?php
namespace Modules\Purchase\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class GoodsReceived extends DomainEvent
{
    /**
     * @param  string       $poId
     * @param  string       $grnId
     * @param  string       $tenantId
     * @param  string|null  $vendorId
     * @param  array<int,array{product_id:string|null,qty_accepted:string,location_id:string|null,unit_price:string|null,tax_rate:string|null,description:string}>  $lines
     */
    public function __construct(
        public readonly string  $poId,
        public readonly string  $grnId,
        public readonly string  $tenantId = '',
        public readonly array   $lines    = [],
        public readonly ?string $vendorId = null,
    ) {}
}
