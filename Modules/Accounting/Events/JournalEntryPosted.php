<?php

declare(strict_types=1);

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Entities\JournalEntry;

/**
 * Journal Entry Posted Event
 *
 * Dispatched when a journal entry is posted.
 */
class JournalEntryPosted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public JournalEntry $journalEntry
    ) {}
}
