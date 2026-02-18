<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Accounting\Events\JournalEntryPosted;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\FiscalPeriodRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;
use Modules\Core\Services\BaseService;

/**
 * Journal Entry Service
 *
 * Handles business logic for journal entry operations.
 * Ensures double-entry bookkeeping rules are followed.
 */
class JournalEntryService extends BaseService
{
    /**
     * JournalEntryService constructor.
     */
    public function __construct(
        protected JournalEntryRepositoryInterface $journalEntryRepository,
        protected JournalEntryLineRepositoryInterface $lineRepository,
        protected AccountRepositoryInterface $accountRepository,
        protected FiscalPeriodRepositoryInterface $fiscalPeriodRepository
    ) {}

    /**
     * Get paginated journal entries.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->journalEntryRepository->paginate($perPage);
    }

    /**
     * Create a new journal entry with lines.
     *
     * @throws \Exception
     */
    public function create(array $data): JournalEntry
    {
        return $this->executeInTransaction(function () use ($data) {
            // Validate fiscal period
            if (empty($data['fiscal_period_id'])) {
                $period = $this->fiscalPeriodRepository->getCurrentPeriod();
                if (! $period) {
                    throw new \Exception('No active fiscal period found');
                }
                $data['fiscal_period_id'] = $period->id;
            } else {
                $period = $this->fiscalPeriodRepository->findById($data['fiscal_period_id']);
                if (! $period || ! $period->canAcceptEntries()) {
                    throw new \Exception('Fiscal period is closed or invalid');
                }
            }

            // Generate entry number if not provided
            if (empty($data['entry_number'])) {
                $data['entry_number'] = $this->generateEntryNumber();
            }

            // Set default values
            $data['status'] = $data['status'] ?? JournalEntry::STATUS_DRAFT;
            $data['entry_date'] = $data['entry_date'] ?? now();
            $data['total_debit'] = 0;
            $data['total_credit'] = 0;

            // Create journal entry
            $entry = $this->journalEntryRepository->create($data);

            // Create journal entry lines if provided
            if (! empty($data['lines'])) {
                foreach ($data['lines'] as $lineData) {
                    $lineData['journal_entry_id'] = $entry->id;
                    $this->lineRepository->create($lineData);
                }

                // Calculate totals
                $entry->calculateTotals();
            }

            $this->logInfo('Journal entry created', [
                'entry_id' => $entry->id,
                'entry_number' => $entry->entry_number,
            ]);

            return $entry->fresh(['lines']);
        });
    }

    /**
     * Update an existing journal entry.
     *
     * @throws \Exception
     */
    public function update(int $id, array $data): JournalEntry
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $entry = $this->journalEntryRepository->findById($id);

            if (! $entry) {
                throw new \Exception('Journal entry not found');
            }

            if ($entry->isPosted()) {
                throw new \Exception('Cannot update posted journal entry');
            }

            // Update entry
            $entry = $this->journalEntryRepository->update($id, $data);

            // Update lines if provided
            if (isset($data['lines'])) {
                // Delete existing lines
                foreach ($entry->lines as $line) {
                    $this->lineRepository->delete($line->id);
                }

                // Create new lines
                foreach ($data['lines'] as $lineData) {
                    $lineData['journal_entry_id'] = $entry->id;
                    $this->lineRepository->create($lineData);
                }

                // Calculate totals
                $entry->calculateTotals();
            }

            $this->logInfo('Journal entry updated', [
                'entry_id' => $entry->id,
            ]);

            return $entry->fresh(['lines']);
        });
    }

    /**
     * Delete a journal entry.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $entry = $this->journalEntryRepository->findById($id);

        if (! $entry) {
            throw new \Exception('Journal entry not found');
        }

        if ($entry->isPosted()) {
            throw new \Exception('Cannot delete posted journal entry');
        }

        // Delete lines first
        foreach ($entry->lines as $line) {
            $this->lineRepository->delete($line->id);
        }

        $result = $this->journalEntryRepository->delete($id);

        if ($result) {
            $this->logInfo('Journal entry deleted', [
                'entry_id' => $id,
            ]);
        }

        return $result;
    }

    /**
     * Post a journal entry.
     *
     * @throws \Exception
     */
    public function post(int $id): JournalEntry
    {
        return $this->executeInTransaction(function () use ($id) {
            $entry = $this->journalEntryRepository->findById($id);

            if (! $entry) {
                throw new \Exception('Journal entry not found');
            }

            if ($entry->isPosted()) {
                throw new \Exception('Journal entry is already posted');
            }

            // Validate balance
            if (! $this->validateBalance($entry)) {
                throw new \Exception('Journal entry is not balanced');
            }

            // Check fiscal period
            $period = $entry->fiscalPeriod;
            if (! $period || ! $period->canAcceptEntries()) {
                throw new \Exception('Fiscal period is closed');
            }

            // Update account balances
            foreach ($entry->lines as $line) {
                $account = $line->account;
                if ($line->isDebit()) {
                    $account->updateBalance($line->debit_amount, true);
                } else {
                    $account->updateBalance($line->credit_amount, false);
                }
            }

            // Update entry status
            $entry->status = JournalEntry::STATUS_POSTED;
            $entry->posted_at = now();
            $entry->posted_by = Auth::id();
            $entry->save();

            event(new JournalEntryPosted($entry));

            $this->logInfo('Journal entry posted', [
                'entry_id' => $entry->id,
                'entry_number' => $entry->entry_number,
            ]);

            return $entry;
        });
    }

    /**
     * Reverse a journal entry.
     *
     * @throws \Exception
     */
    public function reverse(int $id, array $data = []): JournalEntry
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $originalEntry = $this->journalEntryRepository->findById($id);

            if (! $originalEntry) {
                throw new \Exception('Journal entry not found');
            }

            if (! $originalEntry->isPosted()) {
                throw new \Exception('Can only reverse posted journal entries');
            }

            // Create reversal entry
            $reversalData = [
                'entry_date' => $data['entry_date'] ?? now(),
                'reference' => $data['reference'] ?? 'Reversal of '.$originalEntry->entry_number,
                'description' => $data['description'] ?? 'Reversal: '.$originalEntry->description,
                'fiscal_period_id' => $originalEntry->fiscal_period_id,
                'currency' => $originalEntry->currency,
                'lines' => [],
            ];

            // Reverse lines (swap debits and credits)
            foreach ($originalEntry->lines as $line) {
                $reversalData['lines'][] = [
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'debit_amount' => $line->credit_amount,
                    'credit_amount' => $line->debit_amount,
                    'currency' => $line->currency,
                ];
            }

            // Create and post reversal entry
            $reversalEntry = $this->create($reversalData);
            $reversalEntry = $this->post($reversalEntry->id);

            // Mark original as reversed
            $originalEntry->status = JournalEntry::STATUS_REVERSED;
            $originalEntry->reversed_entry_id = $reversalEntry->id;
            $originalEntry->save();

            $this->logInfo('Journal entry reversed', [
                'original_entry_id' => $originalEntry->id,
                'reversal_entry_id' => $reversalEntry->id,
            ]);

            return $reversalEntry;
        });
    }

    /**
     * Validate if journal entry is balanced.
     */
    public function validateBalance(JournalEntry $entry): bool
    {
        $entry->calculateTotals();

        return $entry->isBalanced();
    }

    /**
     * Find journal entry by ID.
     */
    public function findById(int $id): ?JournalEntry
    {
        return $this->journalEntryRepository->findById($id);
    }

    /**
     * Get entries by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->journalEntryRepository->getByStatus($status);
    }

    /**
     * Generate a unique entry number.
     */
    protected function generateEntryNumber(): string
    {
        $prefix = 'JE';
        $year = date('Y');
        $month = date('m');

        // Get the last entry number for this month
        $lastEntry = $this->journalEntryRepository
            ->getModel()
            ->where('entry_number', 'LIKE', "{$prefix}-{$year}{$month}-%")
            ->orderBy('entry_number', 'desc')
            ->first();

        if ($lastEntry) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastEntry->entry_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $sequence);
    }
}
