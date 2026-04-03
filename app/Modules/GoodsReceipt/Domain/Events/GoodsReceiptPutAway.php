<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;

class GoodsReceiptPutAway extends BaseEvent
{
    public function __construct(
        public readonly GoodsReceipt $receipt,
        public readonly int $putAwayBy,
    ) {
        parent::__construct($receipt->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->receipt->getId(),
            'put_away_by' => $this->putAwayBy,
        ]);
    }
}
