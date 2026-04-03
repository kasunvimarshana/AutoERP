<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class GoodsReceiptReceived extends BaseEvent
{
    public function __construct(
        public readonly int $goodsReceiptId,
        public readonly int $receivedBy,
    ) {
        parent::__construct(0);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->goodsReceiptId,
            'received_by' => $this->receivedBy,
        ]);
    }
}
