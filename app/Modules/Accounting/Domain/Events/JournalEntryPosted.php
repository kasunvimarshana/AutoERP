<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class JournalEntryPosted extends BaseEvent
{
    public int $journalEntryId;

    public function __construct(int $tenantId, int $journalEntryId)
    {
        parent::__construct($tenantId);
        $this->journalEntryId = $journalEntryId;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['journalEntryId' => $this->journalEntryId]);
    }
}
