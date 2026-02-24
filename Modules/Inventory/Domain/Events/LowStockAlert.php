<?php
namespace Modules\Inventory\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class LowStockAlert extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $currentQty,
        public readonly string $reorderPoint,
    ) {}
}
