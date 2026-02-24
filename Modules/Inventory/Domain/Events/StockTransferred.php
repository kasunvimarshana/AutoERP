<?php
namespace Modules\Inventory\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class StockTransferred extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $fromLocationId,
        public readonly string $toLocationId,
        public readonly string $qty,
    ) {}
}
