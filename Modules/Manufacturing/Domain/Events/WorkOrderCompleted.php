<?php

namespace Modules\Manufacturing\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class WorkOrderCompleted extends DomainEvent
{
    /**
     * @param  string       $workOrderId
     * @param  string       $tenantId
     * @param  string       $quantityProduced
     * @param  string|null  $finishedProductId   BOM product (finished goods)
     * @param  string|null  $finishedLocationId  Target warehouse location for finished goods
     * @param  array<int,array{product_id:string,qty_consumed:string,location_id:string|null}>  $components
     *         BOM component lines consumed during production
     */
    public function __construct(
        public readonly string  $workOrderId,
        public readonly string  $tenantId,
        public readonly string  $quantityProduced,
        public readonly ?string $finishedProductId  = null,
        public readonly ?string $finishedLocationId = null,
        public readonly array   $components         = [],
    ) {
        parent::__construct();
    }
}
