<?php

namespace Modules\Inventory\Domain\Events;

use DateTimeImmutable;

class StockValuationEntryRecorded
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $entryId,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly string $movementType,
        public readonly string $totalValue,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
