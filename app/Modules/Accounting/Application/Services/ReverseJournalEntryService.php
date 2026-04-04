<?php
namespace Modules\Accounting\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Accounting\Application\Contracts\ReverseJournalEntryServiceInterface;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Events\JournalEntryReversed;
use Modules\Accounting\Domain\Repositories\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\ValueObjects\JournalEntryStatus;

class ReverseJournalEntryService implements ReverseJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(JournalEntry $entry, int $reversedBy): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::POSTED) {
            throw new \DomainException("Journal entry [{$entry->id}] must be posted before it can be reversed.");
        }

        $originalLines = $this->journalEntryRepository->findLines($entry->id);

        $reversal = $this->journalEntryRepository->create([
            'tenant_id'        => $entry->tenantId,
            'reference_number' => 'REV-' . $entry->referenceNumber,
            'status'           => JournalEntryStatus::POSTED,
            'entry_date'       => now()->toDateString(),
            'description'      => 'Reversal of entry #' . $entry->referenceNumber,
            'source_type'      => $entry->sourceType,
            'source_id'        => $entry->sourceId,
            'posted_by'        => $reversedBy,
            'posted_at'        => now()->toDateTimeString(),
        ]);

        foreach ($originalLines as $line) {
            $this->journalEntryRepository->addLine($reversal->id, [
                'journal_entry_id' => $reversal->id,
                'account_id'       => $line->accountId,
                'debit'            => $line->credit,
                'credit'           => $line->debit,
                'currency'         => $line->currency,
                'description'      => $line->description,
            ]);
        }

        $this->journalEntryRepository->update($entry, [
            'status'      => JournalEntryStatus::REVERSED,
            'reversed_by' => $reversedBy,
            'reversed_at' => now()->toDateTimeString(),
        ]);

        $this->dispatcher->dispatch(new JournalEntryReversed($entry->tenantId, $entry->id));

        return $reversal;
    }
}
