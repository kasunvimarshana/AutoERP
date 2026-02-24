<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class InvoiceCreated extends DomainEvent
{
    public function __construct(public readonly string $invoiceId)
    {
        parent::__construct();
    }
}
