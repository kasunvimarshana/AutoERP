<?php
namespace Modules\Inventory\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class StockDeducted extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $qty,
        public readonly string $locationId,
    ) {}
}
