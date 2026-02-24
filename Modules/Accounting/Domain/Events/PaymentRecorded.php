<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class PaymentRecorded extends DomainEvent
{
    public function __construct(public readonly string $invoiceId)
    {
        parent::__construct();
    }
}
