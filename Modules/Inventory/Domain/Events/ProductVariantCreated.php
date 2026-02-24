<?php

namespace Modules\Inventory\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class ProductVariantCreated extends DomainEvent
{
    public function __construct(public readonly string $variantId) {}
}
