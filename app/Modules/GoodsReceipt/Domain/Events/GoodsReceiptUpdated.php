<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class GoodsReceiptUpdated extends BaseEvent
{
    public function __construct(
        public readonly int $goodsReceiptId,
        int $tenantId,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->goodsReceiptId,
            'tenant_id' => $this->tenantId,
        ]);
    }
}
