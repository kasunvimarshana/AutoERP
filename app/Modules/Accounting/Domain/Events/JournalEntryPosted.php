<?php
namespace Modules\Accounting\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class JournalEntryPosted extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $journalEntryId)
    {
        parent::__construct($tenantId);
    }
}
