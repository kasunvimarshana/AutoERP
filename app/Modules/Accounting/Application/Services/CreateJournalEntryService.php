<?php
namespace Modules\Accounting\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Accounting\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Accounting\Application\DTOs\JournalEntryData;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Events\JournalEntryCreated;
use Modules\Accounting\Domain\Repositories\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\ValueObjects\JournalEntryStatus;

class CreateJournalEntryService implements CreateJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(JournalEntryData $data): JournalEntry
    {
        $totalDebits  = 0.0;
        $totalCredits = 0.0;

        foreach ($data->lines as $line) {
            $totalDebits  += (float) ($line['debit']  ?? 0);
            $totalCredits += (float) ($line['credit'] ?? 0);
        }

        if (round($totalDebits, 4) !== round($totalCredits, 4)) {
            throw new \DomainException(
                "Journal entry is not balanced: debits ({$totalDebits}) != credits ({$totalCredits})"
            );
        }

        $entry = $this->journalEntryRepository->create([
            'tenant_id'        => $data->tenantId,
            'reference_number' => $data->referenceNumber,
            'status'           => JournalEntryStatus::DRAFT,
            'entry_date'       => $data->entryDate,
            'description'      => $data->description,
            'source_type'      => $data->sourceType,
            'source_id'        => $data->sourceId,
        ]);

        foreach ($data->lines as $line) {
            $this->journalEntryRepository->addLine($entry->id, [
                'journal_entry_id' => $entry->id,
                'account_id'       => $line['account_id'],
                'debit'            => $line['debit']       ?? 0,
                'credit'           => $line['credit']      ?? 0,
                'currency'         => $line['currency']    ?? 'USD',
                'description'      => $line['description'] ?? null,
            ]);
        }

        $this->dispatcher->dispatch(new JournalEntryCreated($entry->tenantId, $entry->id));

        return $entry;
    }
}
