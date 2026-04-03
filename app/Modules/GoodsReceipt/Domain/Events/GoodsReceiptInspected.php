<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;

class GoodsReceiptInspected extends BaseEvent
{
    public function __construct(
        public readonly GoodsReceipt $receipt,
        public readonly int $inspectedBy,
    ) {
        parent::__construct($receipt->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'           => $this->receipt->getId(),
            'inspected_by' => $this->inspectedBy,
        ]);
    }
}
