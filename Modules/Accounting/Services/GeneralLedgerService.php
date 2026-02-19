<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Modules\Accounting\Enums\JournalEntryStatus;
use Modules\Accounting\Events\JournalEntryCreated;
use Modules\Accounting\Events\JournalEntryPosted;
use Modules\Accounting\Events\JournalEntryReversed;
use Modules\Accounting\Exceptions\FiscalPeriodClosedException;
use Modules\Accounting\Exceptions\InvalidJournalEntryStatusException;
use Modules\Accounting\Exceptions\UnbalancedJournalEntryException;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Repositories\AccountRepository;
use Modules\Accounting\Repositories\FiscalPeriodRepository;
use Modules\Accounting\Repositories\JournalEntryRepository;
use Modules\Core\Helpers\MathHelper;
use Modules\Core\Helpers\TransactionHelper;

/**
 * General Ledger Service
 *
 * Handles journal entries, posting, and general ledger operations
 */
class GeneralLedgerService
{
    public function __construct(
        private JournalEntryRepository $journalEntryRepository,
        private AccountRepository $accountRepository,
        private FiscalPeriodRepository $fiscalPeriodRepository
    ) {}

    /**
     * Create a new journal entry
     */
    public function createJournalEntry(array $data, array $lines = []): JournalEntry
    {
        return TransactionHelper::execute(function () use ($data, $lines) {
            if (empty($data['entry_number'])) {
                $data['entry_number'] = $this->generateEntryNumber();
            }

            $data['status'] = $data['status'] ?? JournalEntryStatus::Draft;

            $fiscalPeriod = $this->fiscalPeriodRepository->findByDate($data['entry_date']);
            if ($fiscalPeriod) {
                $data['fiscal_period_id'] = $fiscalPeriod->id;
            }

            $journalEntry = $this->journalEntryRepository->create($data);

            if (! empty($lines)) {
                foreach ($lines as $index => $line) {
                    $line['journal_entry_id'] = $journalEntry->id;
                    $line['line_number'] = $index + 1;
                    $line['tenant_id'] = $journalEntry->tenant_id;
                    $journalEntry->lines()->create($line);
                }
                $journalEntry->load('lines.account');
            }

            event(new JournalEntryCreated($journalEntry));

            return $journalEntry;
        });
    }

    /**
     * Update journal entry
     */
    public function updateJournalEntry(string $id, array $data, ?array $lines = null): JournalEntry
    {
        $journalEntry = $this->journalEntryRepository->findOrFail($id);

        if (! $journalEntry->isDraft()) {
            throw new InvalidJournalEntryStatusException(
                'Only draft journal entries can be modified'
            );
        }

        return TransactionHelper::execute(function () use ($journalEntry, $data, $lines) {
            if ($lines !== null) {
                $journalEntry->lines()->delete();

                foreach ($lines as $index => $line) {
                    $line['journal_entry_id'] = $journalEntry->id;
                    $line['line_number'] = $index + 1;
                    $line['tenant_id'] = $journalEntry->tenant_id;
                    $journalEntry->lines()->create($line);
                }
            }

            if (isset($data['entry_date'])) {
                $fiscalPeriod = $this->fiscalPeriodRepository->findByDate($data['entry_date']);
                if ($fiscalPeriod) {
                    $data['fiscal_period_id'] = $fiscalPeriod->id;
                }
            }

            return $this->journalEntryRepository->update($journalEntry->id, $data);
        });
    }

    /**
     * Post journal entry to the ledger
     */
    public function postJournalEntry(string $id, string $userId): JournalEntry
    {
        $journalEntry = $this->journalEntryRepository->findOrFail($id);

        if (! $journalEntry->isDraft()) {
            throw new InvalidJournalEntryStatusException(
                'Only draft journal entries can be posted'
            );
        }

        $journalEntry->load('lines');

        if (! $this->validateBalance($journalEntry)) {
            throw new UnbalancedJournalEntryException(
                'Journal entry debits and credits must be equal'
            );
        }

        if ($journalEntry->fiscalPeriod && ! $journalEntry->fiscalPeriod->isOpen()) {
            if (! config('accounting.allow_posting_to_closed_period', false)) {
                throw new FiscalPeriodClosedException(
                    'Cannot post to a closed fiscal period'
                );
            }
        }

        return TransactionHelper::execute(function () use ($journalEntry, $userId) {
            $updated = $this->journalEntryRepository->update($journalEntry->id, [
                'status' => JournalEntryStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);

            event(new JournalEntryPosted($updated));

            return $updated;
        });
    }

    /**
     * Reverse a posted journal entry
     */
    public function reverseJournalEntry(string $id, string $userId, ?string $reversalDate = null): JournalEntry
    {
        $journalEntry = $this->journalEntryRepository->findOrFail($id);

        if (! $journalEntry->isPosted()) {
            throw new InvalidJournalEntryStatusException(
                'Only posted journal entries can be reversed'
            );
        }

        if ($journalEntry->isReversed()) {
            throw new InvalidJournalEntryStatusException(
                'Journal entry has already been reversed'
            );
        }

        return TransactionHelper::execute(function () use ($journalEntry, $userId, $reversalDate) {
            $journalEntry->load('lines');

            $reversalLines = [];
            foreach ($journalEntry->lines as $line) {
                $reversalLines[] = [
                    'account_id' => $line->account_id,
                    'description' => 'Reversal: '.$line->description,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ];
            }

            $reversalEntry = $this->createJournalEntry([
                'tenant_id' => $journalEntry->tenant_id,
                'organization_id' => $journalEntry->organization_id,
                'entry_date' => $reversalDate ?? now()->toDateString(),
                'reference' => 'REV-'.$journalEntry->entry_number,
                'description' => 'Reversal of '.$journalEntry->entry_number.': '.$journalEntry->description,
            ], $reversalLines);

            $reversalEntry = $this->postJournalEntry($reversalEntry->id, $userId);

            $this->journalEntryRepository->update($journalEntry->id, [
                'status' => JournalEntryStatus::Reversed,
                'reversed_at' => now(),
                'reversed_by' => $userId,
                'reversal_entry_id' => $reversalEntry->id,
            ]);

            event(new JournalEntryReversed($journalEntry, $reversalEntry));

            return $reversalEntry;
        });
    }

    /**
     * Delete a draft journal entry
     */
    public function deleteJournalEntry(string $id): bool
    {
        $journalEntry = $this->journalEntryRepository->findOrFail($id);

        if (! $journalEntry->isDraft()) {
            throw new InvalidJournalEntryStatusException(
                'Only draft journal entries can be deleted'
            );
        }

        return TransactionHelper::execute(function () use ($journalEntry) {
            $journalEntry->lines()->delete();

            return $this->journalEntryRepository->delete($journalEntry->id);
        });
    }

    /**
     * Validate journal entry balance
     */
    protected function validateBalance(JournalEntry $journalEntry): bool
    {
        $totalDebits = '0';
        $totalCredits = '0';
        $scale = config('accounting.decimal_scale', 6);

        foreach ($journalEntry->lines as $line) {
            $totalDebits = MathHelper::add($totalDebits, (string) $line->debit, $scale);
            $totalCredits = MathHelper::add($totalCredits, (string) $line->credit, $scale);
        }

        return MathHelper::equals($totalDebits, $totalCredits, $scale);
    }

    /**
     * Generate journal entry number
     */
    protected function generateEntryNumber(): string
    {
        $prefix = config('accounting.journal_entry_prefix', 'JE-');
        $year = now()->year;
        $month = str_pad((string) now()->month, 2, '0', STR_PAD_LEFT);

        $lastEntry = JournalEntry::where('entry_number', 'like', $prefix.$year.$month.'%')
            ->orderBy('entry_number', 'desc')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) substr($lastEntry->entry_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.$month.'-'.str_pad((string) $newNumber, 6, '0', STR_PAD_LEFT);
    }
}
