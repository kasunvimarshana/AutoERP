<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Events\BankTransactionReconciled;

class ReconcileBankTransactionUseCase
{
    public function __construct(
        private BankTransactionRepositoryInterface $transactionRepo,
        private JournalEntryRepositoryInterface    $journalEntryRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $transaction = $this->transactionRepo->findById($data['transaction_id']);
            if (! $transaction) {
                throw new \DomainException('Bank transaction not found.');
            }

            if ($transaction->status === 'reconciled') {
                throw new \DomainException('Bank transaction is already reconciled.');
            }

            $journalEntry = $this->journalEntryRepo->findById($data['journal_entry_id']);
            if (! $journalEntry) {
                throw new \DomainException('Journal entry not found.');
            }

            if ($journalEntry->status === 'draft') {
                throw new \DomainException('Cannot reconcile against a draft journal entry.');
            }

            $updated = $this->transactionRepo->update($data['transaction_id'], [
                'status'           => 'reconciled',
                'journal_entry_id' => $data['journal_entry_id'],
            ]);

            Event::dispatch(new BankTransactionReconciled(
                $updated->id,
                $data['tenant_id'] ?? null,
                $data['journal_entry_id'],
            ));

            return $updated;
        });
    }
}
