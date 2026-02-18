<?php

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Events\JournalEntryPosted;
use App\Modules\Finance\Events\JournalEntryVoided;
use App\Modules\Finance\Services\AccountService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateAccountBalances implements ShouldQueue
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    public function handle(JournalEntryPosted|JournalEntryVoided $event): void
    {
        $journalEntry = $event->journalEntry;

        // Update balance for each account affected by this journal entry
        foreach ($journalEntry->lines as $line) {
            $this->accountService->updateAccountBalance($line->account_id);
        }
    }
}
