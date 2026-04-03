<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Transaction\Domain\Entities\JournalEntry;

class JournalEntryPosted extends BaseEvent
{
    public function __construct(public readonly JournalEntry $journalEntry)
    {
        parent::__construct($journalEntry->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'             => $this->journalEntry->getId(),
            'tenant_id'      => $this->journalEntry->getTenantId(),
            'transaction_id' => $this->journalEntry->getTransactionId(),
            'account_code'   => $this->journalEntry->getAccountCode(),
            'posted_at'      => $this->journalEntry->getPostedAt()?->format('c'),
        ]);
    }
}
