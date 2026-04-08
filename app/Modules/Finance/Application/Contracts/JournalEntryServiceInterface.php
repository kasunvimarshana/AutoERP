<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Finance\Application\DTOs\JournalEntryData;

interface JournalEntryServiceInterface
{
    /**
     * Create a new draft journal entry with its lines.
     */
    public function create(JournalEntryData $dto, int $tenantId): mixed;

    /**
     * Update a draft journal entry.
     * Throws JournalEntryAlreadyPostedException if already posted.
     */
    public function update(int $id, JournalEntryData $dto): mixed;

    /**
     * Post a journal entry: validate balance, update account balances, mark posted.
     * Throws UnbalancedJournalEntryException if debit ≠ credit.
     * Throws JournalEntryAlreadyPostedException if already posted.
     */
    public function post(int $id): mixed;

    /**
     * Void a posted journal entry, recording the reason.
     * Throws JournalEntryAlreadyPostedException if already voided.
     */
    public function void(int $id, string $reason): mixed;

    /**
     * Delete a draft journal entry (soft delete).
     */
    public function delete(int $id): bool;

    /**
     * Find a journal entry with its lines.
     */
    public function find(mixed $id): mixed;

    /**
     * Paginated list of journal entries with optional filters.
     */
    public function list(array $filters = [], ?int $perPage = null): mixed;
}
