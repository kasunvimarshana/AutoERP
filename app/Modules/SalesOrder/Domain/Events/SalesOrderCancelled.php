<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class SalesOrderCancelled extends BaseEvent
{
    public function __construct(
        public readonly int $salesOrderId,
        int $tenantId,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->salesOrderId,
            'tenant_id' => $this->tenantId,
        ]);
    }
}
