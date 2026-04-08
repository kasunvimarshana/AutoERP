<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class JournalEntryVoided extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $journalEntryId,
    ) {
        parent::__construct($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'journalEntryId' => $this->journalEntryId,
        ]);
    }
}
