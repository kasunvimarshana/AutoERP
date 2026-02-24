<?php
namespace Modules\Sales\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class PriceListItemAdded extends DomainEvent
{
    public function __construct(
        public readonly string $priceListId,
        public readonly string $itemId,
    ) {}
}
