<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class SalesOrderShipped extends BaseEvent
{
    public function __construct(
        public readonly int $salesOrderId,
        public readonly int $shippedBy,
    ) {
        parent::__construct(0);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'         => $this->salesOrderId,
            'shipped_by' => $this->shippedBy,
        ]);
    }
}
