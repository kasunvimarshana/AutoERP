<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class JournalEntryPosted extends DomainEvent
{
    public function __construct(public readonly string $journalEntryId)
    {
        parent::__construct();
    }
}
