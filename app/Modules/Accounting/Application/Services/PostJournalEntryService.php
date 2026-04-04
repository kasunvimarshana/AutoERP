<?php
namespace Modules\Accounting\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Accounting\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Events\JournalEntryPosted;
use Modules\Accounting\Domain\Repositories\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\ValueObjects\JournalEntryStatus;

class PostJournalEntryService implements PostJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(JournalEntry $entry, int $postedBy): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::DRAFT) {
            throw new \DomainException("Journal entry [{$entry->id}] is not in draft status.");
        }

        $updated = $this->journalEntryRepository->update($entry, [
            'status'    => JournalEntryStatus::POSTED,
            'posted_by' => $postedBy,
            'posted_at' => now()->toDateTimeString(),
        ]);

        $this->dispatcher->dispatch(new JournalEntryPosted($updated->tenantId, $updated->id));

        return $updated;
    }
}
