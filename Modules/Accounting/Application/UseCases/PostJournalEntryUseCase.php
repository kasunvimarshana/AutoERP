<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\AccountingPeriodRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Events\JournalEntryPosted;

class PostJournalEntryUseCase
{
    public function __construct(
        private JournalEntryRepositoryInterface     $repo,
        private ?AccountingPeriodRepositoryInterface $periodRepo = null,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $entry = $this->repo->findById($data['id']);

            if (! $entry) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Journal entry not found.');
            }

            $totalDebit  = '0.00000000';
            $totalCredit = '0.00000000';

            foreach ($entry->lines as $line) {
                $totalDebit  = bcadd($totalDebit, (string) $line->debit, 8);
                $totalCredit = bcadd($totalCredit, (string) $line->credit, 8);
            }

            if (bccomp($totalDebit, $totalCredit, 8) !== 0) {
                throw new \DomainException(
                    "Journal entry is not balanced: debit={$totalDebit}, credit={$totalCredit}"
                );
            }

            // Reject posting if the entry's date falls within a closed or locked period.
            if ($this->periodRepo && ! empty($entry->entry_date)) {
                $period = $this->periodRepo->findByDate($entry->tenant_id, $entry->entry_date);
                if ($period && in_array($period->status, ['closed', 'locked'], true)) {
                    throw new \DomainException(
                        "Cannot post a journal entry dated within a {$period->status} accounting period ({$period->name})."
                    );
                }
            }

            $posted = $this->repo->update($data['id'], ['status' => 'posted']);

            Event::dispatch(new JournalEntryPosted($posted->id));

            return $posted;
        });
    }
}
