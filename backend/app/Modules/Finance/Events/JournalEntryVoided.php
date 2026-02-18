<?php

namespace App\Modules\Finance\Events;

use App\Modules\Finance\Models\JournalEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JournalEntryVoided
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public JournalEntry $journalEntry
    ) {}
}
