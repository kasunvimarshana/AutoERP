<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class PurchaseOrderCreated extends BaseEvent
{
    public function __construct(
        public readonly int $purchaseOrderId,
        int $tenantId,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->purchaseOrderId,
            'tenant_id' => $this->tenantId,
        ]);
    }
}
