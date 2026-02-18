<?php

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Events\JournalEntryPosted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RecalculateFinancialStatements implements ShouldQueue
{
    public function handle(JournalEntryPosted $event): void
    {
        $journalEntry = $event->journalEntry;

        Log::info('Recalculating financial statements after journal entry posted', [
            'journal_entry_id' => $journalEntry->id,
            'entry_number' => $journalEntry->entry_number,
        ]);

        // Placeholder for future implementation
        // This could trigger caching of financial reports
        // or update materialized views for performance
    }
}
