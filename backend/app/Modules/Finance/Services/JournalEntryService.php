<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\DTOs\CreateJournalEntryDTO;
use App\Modules\Finance\Enums\JournalEntryStatus;
use App\Modules\Finance\Events\JournalEntryPosted;
use App\Modules\Finance\Events\JournalEntryVoided;
use App\Modules\Finance\Models\JournalEntry;
use App\Modules\Finance\Repositories\AccountRepository;
use App\Modules\Finance\Repositories\FiscalYearRepository;
use App\Modules\Finance\Repositories\JournalEntryRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;

/**
 * Journal Entry Service
 *
 * Handles business logic for journal entries including double-entry validation.
 */
class JournalEntryService extends BaseService
{
    public function __construct(
        protected JournalEntryRepository $journalEntryRepository,
        protected AccountRepository $accountRepository,
        protected FiscalYearRepository $fiscalYearRepository,
        protected AccountService $accountService
    ) {}

    public function getAllJournalEntries(int $perPage = 15)
    {
        return $this->journalEntryRepository->paginate($perPage);
    }

    public function createJournalEntry(CreateJournalEntryDTO $dto): JournalEntry
    {
        return $this->transaction(function () use ($dto) {
            $this->logInfo('Creating new journal entry', ['entry_number' => $dto->entryNumber]);

            // Check if entry number already exists
            if ($this->journalEntryRepository->findByEntryNumber($dto->entryNumber)) {
                throw new \InvalidArgumentException("Journal entry with number '{$dto->entryNumber}' already exists");
            }

            // Validate lines
            $this->validateJournalEntryLines($dto->lines);

            // Check fiscal year is open
            $fiscalYear = $this->fiscalYearRepository->getCurrentFiscalYear();
            if (! $fiscalYear) {
                throw new \InvalidArgumentException('No open fiscal year found for the entry date');
            }

            // Create journal entry
            $journalEntryData = $dto->toArray();
            $journalEntryData['tenant_id'] = Auth::user()->tenant_id;

            $journalEntry = $this->journalEntryRepository->create($journalEntryData);

            // Create journal entry lines
            foreach ($dto->lines as $line) {
                $journalEntry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                ]);
            }

            $journalEntry->load(['lines.account', 'lines.costCenter']);

            $this->logInfo('Journal entry created successfully', ['id' => $journalEntry->id]);

            return $journalEntry;
        });
    }

    public function updateJournalEntry(int $id, CreateJournalEntryDTO $dto): JournalEntry
    {
        return $this->transaction(function () use ($id, $dto) {
            $journalEntry = $this->journalEntryRepository->findOrFail($id);

            // Check if can be edited
            if (! $journalEntry->status->canEdit()) {
                throw new \InvalidArgumentException(
                    "Journal entry with status '{$journalEntry->status->label()}' cannot be edited"
                );
            }

            $this->logInfo('Updating journal entry', ['id' => $id]);

            // Check if entry number changed and already exists
            if ($journalEntry->entry_number !== $dto->entryNumber
                && $this->journalEntryRepository->findByEntryNumber($dto->entryNumber)) {
                throw new \InvalidArgumentException("Journal entry with number '{$dto->entryNumber}' already exists");
            }

            // Validate lines
            $this->validateJournalEntryLines($dto->lines);

            // Update journal entry
            $this->journalEntryRepository->update($id, $dto->toArray());

            // Delete existing lines
            $journalEntry->lines()->delete();

            // Create new lines
            foreach ($dto->lines as $line) {
                $journalEntry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                ]);
            }

            $journalEntry->refresh();
            $journalEntry->load(['lines.account', 'lines.costCenter']);

            $this->logInfo('Journal entry updated successfully', ['id' => $id]);

            return $journalEntry;
        });
    }

    public function deleteJournalEntry(int $id): bool
    {
        $journalEntry = $this->journalEntryRepository->findOrFail($id);

        // Check if can be deleted
        if (! $journalEntry->status->canEdit()) {
            throw new \InvalidArgumentException(
                "Journal entry with status '{$journalEntry->status->label()}' cannot be deleted"
            );
        }

        $this->logInfo('Deleting journal entry', ['id' => $id]);

        // Delete lines first
        $journalEntry->lines()->delete();

        $result = $this->journalEntryRepository->delete($id);

        if ($result) {
            $this->logInfo('Journal entry deleted successfully', ['id' => $id]);
        }

        return $result;
    }

    public function getJournalEntryById(int $id): JournalEntry
    {
        return $this->journalEntryRepository->findWithRelations($id);
    }

    public function postJournalEntry(int $id): JournalEntry
    {
        return $this->transaction(function () use ($id) {
            $journalEntry = $this->journalEntryRepository->findWithRelations($id);

            // Check if can be posted
            if (! $journalEntry->status->canPost()) {
                throw new \InvalidArgumentException(
                    "Journal entry with status '{$journalEntry->status->label()}' cannot be posted"
                );
            }

            $this->logInfo('Posting journal entry', ['id' => $id]);

            // Validate entry is balanced
            if (! $journalEntry->isBalanced()) {
                throw new \InvalidArgumentException(
                    'Journal entry is not balanced. Total debits must equal total credits'
                );
            }

            // Check fiscal year is open
            $fiscalYear = $this->fiscalYearRepository->getCurrentFiscalYear();
            if (! $fiscalYear) {
                throw new \InvalidArgumentException('No open fiscal year found');
            }

            // Update status
            $this->journalEntryRepository->update($id, [
                'status' => JournalEntryStatus::POSTED->value,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            $journalEntry->refresh();
            $journalEntry->load(['lines.account', 'lines.costCenter', 'poster']);

            // Dispatch event
            event(new JournalEntryPosted($journalEntry));

            $this->logInfo('Journal entry posted successfully', ['id' => $id]);

            return $journalEntry;
        });
    }

    public function voidJournalEntry(int $id): JournalEntry
    {
        return $this->transaction(function () use ($id) {
            $journalEntry = $this->journalEntryRepository->findOrFail($id);

            // Check if can be voided
            if (! $journalEntry->status->canVoid()) {
                throw new \InvalidArgumentException(
                    "Journal entry with status '{$journalEntry->status->label()}' cannot be voided"
                );
            }

            $this->logInfo('Voiding journal entry', ['id' => $id]);

            // Update status
            $this->journalEntryRepository->update($id, [
                'status' => JournalEntryStatus::VOID->value,
            ]);

            $journalEntry->refresh();
            $journalEntry->load(['lines.account', 'lines.costCenter']);

            // Dispatch event
            event(new JournalEntryVoided($journalEntry));

            $this->logInfo('Journal entry voided successfully', ['id' => $id]);

            return $journalEntry;
        });
    }

    public function getJournalEntriesByStatus(JournalEntryStatus $status)
    {
        return $this->journalEntryRepository->getByStatus($status);
    }

    public function getDraftEntries()
    {
        return $this->journalEntryRepository->getDraftEntries();
    }

    public function getPostedEntries()
    {
        return $this->journalEntryRepository->getPostedEntries();
    }

    public function searchJournalEntries(string $search)
    {
        return $this->journalEntryRepository->search($search);
    }

    public function getEntriesByDateRange(string $startDate, string $endDate)
    {
        return $this->journalEntryRepository->getEntriesByDateRange($startDate, $endDate);
    }

    protected function validateJournalEntryLines(array $lines): void
    {
        if (empty($lines)) {
            throw new \InvalidArgumentException('Journal entry must have at least one line');
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            // Validate account exists
            if (! isset($line['account_id'])) {
                throw new \InvalidArgumentException('Each line must have an account_id');
            }

            $account = $this->accountRepository->find($line['account_id']);
            if (! $account) {
                throw new \InvalidArgumentException("Account with ID {$line['account_id']} not found");
            }

            $debit = $line['debit'] ?? 0;
            $credit = $line['credit'] ?? 0;

            // Validate amounts
            if ($debit < 0 || $credit < 0) {
                throw new \InvalidArgumentException('Debit and credit amounts must be non-negative');
            }

            if ($debit > 0 && $credit > 0) {
                throw new \InvalidArgumentException('A line cannot have both debit and credit amounts');
            }

            if ($debit == 0 && $credit == 0) {
                throw new \InvalidArgumentException('A line must have either a debit or credit amount');
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        // Validate balance
        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw new \InvalidArgumentException(
                "Total debits ($totalDebit) must equal total credits ($totalCredit)"
            );
        }
    }

    public function generateEntryNumber(): string
    {
        $prefix = 'JE';
        $date = now()->format('Ymd');
        $count = $this->journalEntryRepository->count(['entry_number' => "{$prefix}-{$date}-%"]) + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}
