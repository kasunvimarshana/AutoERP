<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class InvoicePosted extends DomainEvent
{
    public function __construct(public readonly string $invoiceId)
    {
        parent::__construct();
    }
}
