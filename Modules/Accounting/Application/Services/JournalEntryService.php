<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Commands\CreateJournalEntryCommand;
use Modules\Accounting\Application\Commands\DeleteJournalEntryCommand;
use Modules\Accounting\Application\Commands\PostJournalEntryCommand;
use Modules\Accounting\Application\Handlers\CreateJournalEntryHandler;
use Modules\Accounting\Application\Handlers\DeleteJournalEntryHandler;
use Modules\Accounting\Application\Handlers\PostJournalEntryHandler;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Entities\JournalEntry;

/**
 * Service orchestrating all journal-entry operations.
 *
 * Controllers must interact with the journal-entry domain exclusively through
 * this service. Read operations are fulfilled directly via the repository
 * contract; write operations are delegated to the appropriate command handlers.
 */
class JournalEntryService
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly CreateJournalEntryHandler $createJournalEntryHandler,
        private readonly PostJournalEntryHandler $postJournalEntryHandler,
        private readonly DeleteJournalEntryHandler $deleteJournalEntryHandler,
    ) {}

    /**
     * Retrieve a paginated list of journal entries for the given tenant.
     *
     * @return array{items: JournalEntry[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listJournalEntries(int $tenantId, int $page, int $perPage): array
    {
        return $this->journalEntryRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single journal entry by its identifier within the given tenant.
     */
    public function findJournalEntryById(int $entryId, int $tenantId): ?JournalEntry
    {
        return $this->journalEntryRepository->findById($entryId, $tenantId);
    }

    /**
     * Create a new journal entry and return the persisted entity.
     */
    public function createJournalEntry(CreateJournalEntryCommand $command): JournalEntry
    {
        return $this->createJournalEntryHandler->handle($command);
    }

    /**
     * Post a draft journal entry and return the updated entity.
     */
    public function postJournalEntry(PostJournalEntryCommand $command): JournalEntry
    {
        return $this->postJournalEntryHandler->handle($command);
    }

    /**
     * Delete a journal entry.
     */
    public function deleteJournalEntry(DeleteJournalEntryCommand $command): void
    {
        $this->deleteJournalEntryHandler->handle($command);
    }
}
