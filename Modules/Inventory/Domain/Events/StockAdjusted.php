<?php
namespace Modules\Inventory\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class StockAdjusted extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $locationId,
        public readonly string $qty,
        public readonly string $reason,
    ) {}
}
