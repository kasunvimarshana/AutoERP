<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class PurchaseOrderApproved extends BaseEvent
{
    public function __construct(
        public readonly int $purchaseOrderId,
        public readonly int $approvedBy,
    ) {
        parent::__construct(0);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->purchaseOrderId,
            'approved_by' => $this->approvedBy,
        ]);
    }
}
