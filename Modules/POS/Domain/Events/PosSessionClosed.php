<?php

namespace Modules\POS\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class PosSessionClosed extends DomainEvent
{
    public function __construct(public readonly string $sessionId)
    {
        parent::__construct();
    }
}
