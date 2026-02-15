<?php

declare(strict_types=1);

namespace App\Events\Warehouse;

use App\Core\Events\BaseEvent;

/**
 * Picking Completed Event
 * 
 * Dispatched when warehouse picking operation is completed
 */
class PickingCompleted extends BaseEvent
{
    public function __construct(
        public readonly int $pickingListId,
        public readonly string $pickingListNumber,
        public readonly ?int $orderId = null,
        public readonly ?string $orderNumber = null,
        public readonly int $itemCount = 0,
        public readonly \DateTimeInterface $completedAt,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'picking_list_id' => $this->pickingListId,
            'picking_list_number' => $this->pickingListNumber,
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'item_count' => $this->itemCount,
            'completed_at' => $this->completedAt->format('Y-m-d H:i:s'),
        ]);
    }
}
