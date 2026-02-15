<?php

declare(strict_types=1);

namespace App\Events\Warehouse;

use App\Core\Events\BaseEvent;

/**
 * Putaway Completed Event
 * 
 * Dispatched when warehouse putaway operation is completed
 */
class PutawayCompleted extends BaseEvent
{
    public function __construct(
        public readonly int $putawayId,
        public readonly string $putawayNumber,
        public readonly int $locationId,
        public readonly string $locationName,
        public readonly int $itemCount,
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
            'putaway_id' => $this->putawayId,
            'putaway_number' => $this->putawayNumber,
            'location_id' => $this->locationId,
            'location_name' => $this->locationName,
            'item_count' => $this->itemCount,
            'completed_at' => $this->completedAt->format('Y-m-d H:i:s'),
        ]);
    }
}
