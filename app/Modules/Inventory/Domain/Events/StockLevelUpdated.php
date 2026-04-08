<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class StockLevelUpdated extends BaseEvent
{
    public function __construct(
        public readonly mixed $stockItem,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'stock_item_id'       => $this->stockItem->id ?? null,
            'product_id'          => $this->stockItem->product_id ?? null,
            'location_id'         => $this->stockItem->location_id ?? null,
            'quantity_on_hand'    => $this->stockItem->quantity_on_hand ?? null,
            'quantity_available'  => $this->stockItem->quantity_available ?? null,
        ]);
    }
}
