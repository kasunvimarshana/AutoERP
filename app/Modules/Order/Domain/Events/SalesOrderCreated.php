<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class SalesOrderCreated extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $orderId,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['orderId' => $this->orderId]);
    }
}
