<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

/**
 * Tolerance for double-entry bookkeeping validation
 * Used when comparing total debits and credits to account for floating point precision
 */
const BALANCE_TOLERANCE = 0.01;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Repositories\AccountRepository;
use Modules\Accounting\Repositories\JournalEntryRepository;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;

/**
 * Journal Entry Service
 *
 * Handles all business logic for journal entry management.
 */
class JournalEntryService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected JournalEntryRepository $repository,
        protected AccountRepository $accountRepository,
        protected AccountService $accountService
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all journal entries with optional filters.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->repository->query();

        if (isset($filters['is_posted'])) {
            $query->where('is_posted', $filters['is_posted']);
        }

        if (isset($filters['from_date'])) {
            $query->where('entry_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('entry_date', '<=', $filters['to_date']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('entry_number', 'like', "%{$filters['search']}%")
                    ->orWhere('reference', 'like', "%{$filters['search']}%");
            });
        }

        $query->with(['lines.account']);
        $query->orderBy('entry_date', 'desc');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new journal entry.
     */
    public function create(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            // Generate entry number if not provided
            if (empty($data['entry_number'])) {
                $data['entry_number'] = $this->generateEntryNumber();
            }

            // Calculate totals and validate
            $this->validateAndCalculateTotals($data);

            // Set defaults
            $data['is_posted'] = $data['is_posted'] ?? false;
            $data['currency_code'] = $data['currency_code'] ?? config('app.default_currency', 'USD');

            // Create journal entry
            $entry = $this->repository->create($data);

            // Create journal entry lines
            if (isset($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $lineData) {
                    $lineData['journal_entry_id'] = $entry->id;
                    $entry->lines()->create($lineData);
                }
            }

            // If posted, update account balances
            if ($entry->is_posted) {
                $this->postEntry($entry);
            }

            return $entry->load(['lines.account']);
        });
    }

    /**
     * Update an existing journal entry.
     */
    public function update(string $id, array $data): JournalEntry
    {
        return DB::transaction(function () use ($id, $data) {
            $entry = $this->repository->findOrFail($id);

            // Cannot edit posted entries
            if ($entry->is_posted) {
                throw new \Exception('Cannot edit posted journal entry.');
            }

            // Validate if lines are being updated
            if (isset($data['lines'])) {
                $this->validateAndCalculateTotals($data);

                // Delete old lines and create new ones
                $entry->lines()->delete();
                foreach ($data['lines'] as $lineData) {
                    $lineData['journal_entry_id'] = $entry->id;
                    $entry->lines()->create($lineData);
                }
            }

            $entry->update($data);

            return $entry->load(['lines.account']);
        });
    }

    /**
     * Post a journal entry.
     */
    public function postEntry(JournalEntry $entry): JournalEntry
    {
        return DB::transaction(function () use ($entry) {
            if ($entry->is_posted) {
                throw new \Exception('Journal entry is already posted.');
            }

            // Update account balances
            foreach ($entry->lines as $line) {
                $account = $line->account;
                $amount = $line->debit_amount - $line->credit_amount;

                // Adjust for account type (debit increases assets/expenses, credit increases liabilities/equity/revenue)
                if ($account->type->hasCreditBalance()) {
                    $amount = -$amount;
                }

                $this->accountService->updateBalance($account->id, $amount);
            }

            // Mark as posted
            $entry->is_posted = true;
            $entry->posted_at = now();
            $entry->posted_by = auth()->id();
            $entry->save();

            return $entry->load(['lines.account']);
        });
    }

    /**
     * Generate a unique entry number.
     */
    protected function generateEntryNumber(): string
    {
        $prefix = config('accounting.journal_prefix', 'JE');
        $year = date('Y');

        return DB::transaction(function () use ($prefix, $year) {
            $lastEntry = $this->repository->query()
                ->where('entry_number', 'like', "{$prefix}-{$year}-%")
                ->orderBy('entry_number', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastEntry && preg_match('/-(\d+)$/', $lastEntry->entry_number, $matches)) {
                $newNumber = (int) $matches[1] + 1;
            } else {
                $newNumber = 1;
            }

            return $prefix.'-'.$year.'-'.str_pad((string) $newNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Validate journal entry lines and calculate totals.
     */
    protected function validateAndCalculateTotals(array &$data): void
    {
        if (! isset($data['lines']) || ! is_array($data['lines']) || count($data['lines']) < 2) {
            throw new \Exception('Journal entry must have at least 2 lines.');
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($data['lines'] as $line) {
            // Validate account exists
            $this->accountRepository->findOrFail($line['account_id']);

            $debit = $line['debit_amount'] ?? 0;
            $credit = $line['credit_amount'] ?? 0;

            // A line cannot have both debit and credit
            if ($debit > 0 && $credit > 0) {
                throw new \Exception('A journal entry line cannot have both debit and credit amounts.');
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        // Debits must equal credits (within tolerance for floating point precision)
        if (abs($totalDebit - $totalCredit) > BALANCE_TOLERANCE) {
            throw new \Exception("Total debits ($totalDebit) must equal total credits ($totalCredit).");
        }

        $data['total_debit'] = $totalDebit;
        $data['total_credit'] = $totalCredit;
    }
}
