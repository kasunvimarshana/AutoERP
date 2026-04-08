<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventoryLevel;

class StockReleased extends BaseEvent
{
    public function __construct(
        public readonly InventoryLevel $level,
        public readonly float $releasedQty,
    ) {
        parent::__construct($level->getTenantId(), $level->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'           => $this->level->getId(),
            'product_id'   => $this->level->getProductId(),
            'released_qty' => $this->releasedQty,
            'qty_reserved' => $this->level->getQtyReserved(),
        ]);
    }
}
