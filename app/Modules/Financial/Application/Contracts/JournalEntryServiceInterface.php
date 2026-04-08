<?php

declare(strict_types=1);

namespace Modules\Financial\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface JournalEntryServiceInterface extends ServiceInterface
{
    /**
     * Create a new balanced journal entry (header + lines).
     * Throws InvalidJournalEntryException if debits ≠ credits.
     */
    public function createJournalEntry(array $data): mixed;

    /**
     * Post a draft journal entry (change status to 'posted').
     */
    public function postJournalEntry(string $id): mixed;

    /**
     * Void a posted journal entry.
     */
    public function voidJournalEntry(string $id, string $reason): mixed;
}
