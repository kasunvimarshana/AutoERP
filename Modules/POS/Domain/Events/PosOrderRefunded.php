<?php

namespace Modules\POS\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class PosOrderRefunded extends DomainEvent
{
    public function __construct(public readonly string $orderId)
    {
        parent::__construct();
    }
}
