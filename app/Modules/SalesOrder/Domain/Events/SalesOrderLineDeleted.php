<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class SalesOrderLineDeleted extends BaseEvent
{
    public function __construct(
        public readonly int $lineId,
        public readonly int $salesOrderId,
    ) {
        parent::__construct(0);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'             => $this->lineId,
            'sales_order_id' => $this->salesOrderId,
        ]);
    }
}
