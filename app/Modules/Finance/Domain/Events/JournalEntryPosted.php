<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class JournalEntryPosted extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly int    $journalEntryId,
        public readonly string $referenceNumber,
        public readonly float  $totalDebit,
        public readonly float  $totalCredit,
        public readonly int    $postedBy,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastAs(): string
    {
        return 'journal_entry.posted';
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'journal_entry_id' => $this->journalEntryId,
            'reference_number' => $this->referenceNumber,
            'total_debit'      => $this->totalDebit,
            'total_credit'     => $this->totalCredit,
            'posted_by'        => $this->postedBy,
        ]);
    }
}
