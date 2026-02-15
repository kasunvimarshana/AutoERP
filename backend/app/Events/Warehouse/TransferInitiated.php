<?php

declare(strict_types=1);

namespace App\Events\Warehouse;

use App\Core\Events\BaseEvent;

/**
 * Transfer Initiated Event
 * 
 * Dispatched when a warehouse transfer is initiated
 */
class TransferInitiated extends BaseEvent
{
    public function __construct(
        public readonly int $transferId,
        public readonly string $transferNumber,
        public readonly int $fromLocationId,
        public readonly int $toLocationId,
        public readonly string $fromLocationName,
        public readonly string $toLocationName,
        public readonly int $itemCount,
        public readonly ?\DateTimeInterface $expectedDate = null,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'transfer_id' => $this->transferId,
            'transfer_number' => $this->transferNumber,
            'from_location_id' => $this->fromLocationId,
            'to_location_id' => $this->toLocationId,
            'from_location_name' => $this->fromLocationName,
            'to_location_name' => $this->toLocationName,
            'item_count' => $this->itemCount,
            'expected_date' => $this->expectedDate?->format('Y-m-d'),
        ]);
    }
}
