<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\CreateFiscalPeriodDTO;
use Modules\Accounting\Application\DTOs\CreateJournalEntryDTO;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use Modules\Accounting\Domain\Entities\AutoPostingRule;
use Modules\Accounting\Domain\Entities\FiscalPeriod;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Core\Application\Helpers\DecimalHelper;
use Modules\Core\Domain\Contracts\ServiceContract;

/**
 * Accounting service.
 *
 * Orchestrates all accounting use cases: journal entry creation, posting,
 * and listing. Enforces double-entry balance validation using BCMath.
 */
class AccountingService implements ServiceContract
{
    public function __construct(
        private readonly JournalEntryRepositoryContract $journalEntryRepository,
        private readonly AccountRepositoryContract $accountRepository,
        private readonly FiscalPeriodRepositoryContract $fiscalPeriodRepository,
    ) {}

    /**
     * Create a new journal entry with debit/credit lines.
     *
     * Validates that total debits equal total credits (double-entry rule).
     * All amount arithmetic uses DecimalHelper (BCMath) — no float allowed.
     *
     * @throws \InvalidArgumentException When debits do not equal credits.
     */
    public function createJournalEntry(CreateJournalEntryDTO $dto): JournalEntry
    {
        $this->assertDoubleEntryBalance($dto->lines);

        return DB::transaction(function () use ($dto): JournalEntry {
            /** @var JournalEntry $entry */
            $entry = $this->journalEntryRepository->create([
                'fiscal_period_id' => $dto->fiscalPeriodId,
                'reference_number' => $dto->referenceNumber,
                'description'      => $dto->description,
                'entry_date'       => $dto->entryDate,
                'status'           => JournalEntry::STATUS_DRAFT,
            ]);

            foreach ($dto->lines as $line) {
                $entry->lines()->create([
                    'tenant_id'   => $entry->tenant_id,
                    'account_id'  => (int) $line['account_id'],
                    'type'        => $line['type'],
                    'amount'      => DecimalHelper::round((string) $line['amount'], DecimalHelper::SCALE_STANDARD),
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->load('lines');
        });
    }

    /**
     * Post a draft journal entry.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function postEntry(int $entryId): JournalEntry
    {
        return DB::transaction(function () use ($entryId): JournalEntry {
            /** @var JournalEntry $entry */
            $entry = $this->journalEntryRepository->findOrFail($entryId);

            $entry->update([
                'status'    => JournalEntry::STATUS_POSTED,
                'posted_at' => now(),
            ]);

            return $entry->fresh();
        });
    }

    /**
     * List journal entries for the current tenant with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function listEntries(array $filters = []): Collection
    {
        return $this->journalEntryRepository->all();
    }

    /**
     * Return all chart-of-accounts entries for the current tenant.
     */
    public function listAccounts(): Collection
    {
        return $this->accountRepository->all();
    }

    /**
     * Create a new chart-of-accounts entry.
     *
     * @param array<string, mixed> $data
     */
    public function createAccount(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data): \Illuminate\Database\Eloquent\Model {
            return $this->accountRepository->create($data);
        });
    }

    /**
     * Return all fiscal periods for the current tenant.
     */
    public function listFiscalPeriods(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->fiscalPeriodRepository->all();
    }

    /**
     * Create a new fiscal period.
     */
    public function createFiscalPeriod(CreateFiscalPeriodDTO $dto): FiscalPeriod
    {
        return DB::transaction(function () use ($dto): FiscalPeriod {
            /** @var FiscalPeriod $period */
            $period = $this->fiscalPeriodRepository->create([
                'name'       => $dto->name,
                'start_date' => $dto->startDate,
                'end_date'   => $dto->endDate,
                'is_closed'  => $dto->isClosed,
            ]);

            return $period;
        });
    }

    /**
     * Close a fiscal period (mark as closed — immutable after this point).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function closeFiscalPeriod(int $periodId): FiscalPeriod
    {
        return DB::transaction(function () use ($periodId): FiscalPeriod {
            /** @var FiscalPeriod $period */
            $period = $this->fiscalPeriodRepository->findOrFail($periodId);

            $period->update(['is_closed' => true]);

            return $period->fresh();
        });
    }

    /**
     * Show a single chart of account by ID.
     */
    public function showAccount(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->accountRepository->findOrFail($id);
    }

    /**
     * Update an existing account.
     * @param array<string, mixed> $data
     */
    public function updateAccount(int|string $id, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(fn () => $this->accountRepository->update($id, $data));
    }

    /**
     * Show a single journal entry by ID.
     */
    public function showJournalEntry(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->journalEntryRepository->findOrFail($id);
    }

    /**
     * Show a single fiscal period by ID.
     */
    public function showFiscalPeriod(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->fiscalPeriodRepository->findOrFail($id);
    }

    /**
     * Generate a trial balance for a given fiscal period.
     *
     * Aggregates posted journal entry lines by account, computing total debits,
     * total credits, and net balance per account. All arithmetic uses BCMath.
     *
     * @return array<int, array{account_id: int, account_code: string, account_name: string, total_debit: string, total_credit: string, net_balance: string}>
     */
    public function getTrialBalance(int $fiscalPeriodId): array
    {
        $lines = $this->journalEntryRepository->findPostedLinesByPeriod($fiscalPeriodId);

        /** @var array<int, array{account_id: int, account_code: string, account_name: string, total_debit: string, total_credit: string, net_balance: string}> $totals */
        $totals = [];

        foreach ($lines as $line) {
            $accountId = (int) $line->account_id;

            if (! isset($totals[$accountId])) {
                $totals[$accountId] = [
                    'account_id'   => $accountId,
                    'account_code' => $line->account->code ?? '',
                    'account_name' => $line->account->name ?? '',
                    'total_debit'  => '0.0000',
                    'total_credit' => '0.0000',
                    'net_balance'  => '0.0000',
                ];
            }

            if ($line->type === 'debit') {
                $totals[$accountId]['total_debit'] = DecimalHelper::add(
                    $totals[$accountId]['total_debit'],
                    (string) $line->amount,
                    DecimalHelper::SCALE_STANDARD
                );
            } else {
                $totals[$accountId]['total_credit'] = DecimalHelper::add(
                    $totals[$accountId]['total_credit'],
                    (string) $line->amount,
                    DecimalHelper::SCALE_STANDARD
                );
            }

            $totals[$accountId]['net_balance'] = DecimalHelper::sub(
                $totals[$accountId]['total_debit'],
                $totals[$accountId]['total_credit'],
                DecimalHelper::SCALE_STANDARD
            );
        }

        return array_values($totals);
    }

    /**
     * Generate a profit and loss summary for a given fiscal period.
     *
     * Summarises total revenue (credit-normal accounts of type 'Revenue') versus
     * total expenses (debit-normal accounts of type 'Expense'), then computes
     * net profit/loss using BCMath.
     *
     * @return array{total_revenue: string, total_expense: string, net_profit: string}
     */
    public function getProfitAndLoss(int $fiscalPeriodId): array
    {
        $lines = $this->journalEntryRepository->findPostedLinesByPeriod($fiscalPeriodId);

        $totalRevenue = '0.0000';
        $totalExpense = '0.0000';

        foreach ($lines as $line) {
            // Account type codes are stored in lowercase (enforced by design).
            // strtolower() is applied as a defensive normalisation in case the
            // database contains mixed-case values from data imports.
            $typeCode = strtolower($line->account?->accountType?->code ?? '');

            if ($typeCode === 'revenue') {
                // Revenue accounts: credits increase revenue, debits decrease it
                $lineNet = ($line->type === 'credit')
                    ? $line->amount
                    : DecimalHelper::sub('0', (string) $line->amount, DecimalHelper::SCALE_STANDARD);

                $totalRevenue = DecimalHelper::add($totalRevenue, (string) $lineNet, DecimalHelper::SCALE_STANDARD);
            } elseif ($typeCode === 'expense') {
                // Expense accounts: debits increase expense, credits decrease it
                $lineNet = ($line->type === 'debit')
                    ? $line->amount
                    : DecimalHelper::sub('0', (string) $line->amount, DecimalHelper::SCALE_STANDARD);

                $totalExpense = DecimalHelper::add($totalExpense, (string) $lineNet, DecimalHelper::SCALE_STANDARD);
            }
        }

        $netProfit = DecimalHelper::sub($totalRevenue, $totalExpense, DecimalHelper::SCALE_STANDARD);

        return [
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit'    => $netProfit,
        ];
    }

    /**
     * Generate a balance sheet for a given fiscal period.
     *
     * Classifies posted journal entry lines into Assets, Liabilities, and Equity
     * sections using account type codes. All arithmetic uses BCMath exclusively.
     *
     * Account type classification:
     *   - 'asset'     → Assets section (debit-normal: debits increase, credits decrease)
     *   - 'liability' → Liabilities section (credit-normal: credits increase, debits decrease)
     *   - 'equity'    → Equity section (credit-normal: credits increase, debits decrease)
     *
     * @return array{
     *     assets: array<int, array{account_id: int, account_code: string, account_name: string, balance: string}>,
     *     liabilities: array<int, array{account_id: int, account_code: string, account_name: string, balance: string}>,
     *     equity: array<int, array{account_id: int, account_code: string, account_name: string, balance: string}>,
     *     total_assets: string,
     *     total_liabilities: string,
     *     total_equity: string
     * }
     */
    public function getBalanceSheet(int $fiscalPeriodId): array
    {
        $lines = $this->journalEntryRepository->findPostedLinesByPeriod($fiscalPeriodId);

        /** @var array<string, array<int, array{account_id: int, account_code: string, account_name: string, balance: string}>> $sections */
        $sections = [
            'assets'      => [],
            'liabilities' => [],
            'equity'      => [],
        ];

        foreach ($lines as $line) {
            $typeCode  = strtolower($line->account?->accountType?->code ?? '');
            $accountId = (int) $line->account_id;

            if (! in_array($typeCode, ['asset', 'liability', 'equity'], true)) {
                continue;
            }

            $sectionKey = match ($typeCode) {
                'asset'     => 'assets',
                'liability' => 'liabilities',
                'equity'    => 'equity',
            };

            if (! isset($sections[$sectionKey][$accountId])) {
                $sections[$sectionKey][$accountId] = [
                    'account_id'   => $accountId,
                    'account_code' => $line->account->code ?? '',
                    'account_name' => $line->account->name ?? '',
                    'balance'      => '0.0000',
                ];
            }

            if ($typeCode === 'asset') {
                // Assets are debit-normal: debits increase, credits decrease
                $delta = ($line->type === 'debit') ? (string) $line->amount : DecimalHelper::sub('0', (string) $line->amount, DecimalHelper::SCALE_STANDARD);
            } else {
                // Liabilities and Equity are credit-normal: credits increase, debits decrease
                $delta = ($line->type === 'credit') ? (string) $line->amount : DecimalHelper::sub('0', (string) $line->amount, DecimalHelper::SCALE_STANDARD);
            }

            $sections[$sectionKey][$accountId]['balance'] = DecimalHelper::add(
                $sections[$sectionKey][$accountId]['balance'],
                $delta,
                DecimalHelper::SCALE_STANDARD
            );
        }

        $totalAssets      = '0.0000';
        $totalLiabilities = '0.0000';
        $totalEquity      = '0.0000';

        foreach ($sections['assets'] as $row) {
            $totalAssets = DecimalHelper::add($totalAssets, $row['balance'], DecimalHelper::SCALE_STANDARD);
        }
        foreach ($sections['liabilities'] as $row) {
            $totalLiabilities = DecimalHelper::add($totalLiabilities, $row['balance'], DecimalHelper::SCALE_STANDARD);
        }
        foreach ($sections['equity'] as $row) {
            $totalEquity = DecimalHelper::add($totalEquity, $row['balance'], DecimalHelper::SCALE_STANDARD);
        }

        return [
            'assets'            => array_values($sections['assets']),
            'liabilities'       => array_values($sections['liabilities']),
            'equity'            => array_values($sections['equity']),
            'total_assets'      => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity'      => $totalEquity,
        ];
    }

    /**
     * Validate that the sum of debit amounts equals the sum of credit amounts.
     *
     * Uses BCMath exclusively — no float arithmetic.
     *
     * @param array<int, array{account_id: int, type: string, amount: string, description?: string|null}> $lines
     *
     * @throws \InvalidArgumentException
     */
    private function assertDoubleEntryBalance(array $lines): void
    {
        $totalDebits  = '0';
        $totalCredits = '0';

        foreach ($lines as $line) {
            $amount = DecimalHelper::round((string) $line['amount'], DecimalHelper::SCALE_STANDARD);

            if ($line['type'] === 'debit') {
                $totalDebits = DecimalHelper::add($totalDebits, $amount, DecimalHelper::SCALE_STANDARD);
            } else {
                $totalCredits = DecimalHelper::add($totalCredits, $amount, DecimalHelper::SCALE_STANDARD);
            }
        }

        if (! DecimalHelper::equals($totalDebits, $totalCredits, DecimalHelper::SCALE_STANDARD)) {
            throw new \InvalidArgumentException(
                "Journal entry is unbalanced: total debits ({$totalDebits}) do not equal total credits ({$totalCredits})."
            );
        }
    }

    // -------------------------------------------------------------------------
    // Auto-Posting Rules
    // -------------------------------------------------------------------------

    /**
     * Return all auto-posting rules for the current tenant.
     */
    public function listAutoPostingRules(): Collection
    {
        return AutoPostingRule::query()->get();
    }

    /**
     * Create a new auto-posting rule.
     *
     * Defines which accounts to debit and credit when a given event_type occurs.
     *
     * @param array<string, mixed> $data
     */
    public function createAutoPostingRule(array $data): AutoPostingRule
    {
        return DB::transaction(function () use ($data): AutoPostingRule {
            /** @var AutoPostingRule $rule */
            $rule = AutoPostingRule::create([
                'name'              => $data['name'],
                'event_type'        => $data['event_type'],
                'debit_account_id'  => (int) $data['debit_account_id'],
                'credit_account_id' => (int) $data['credit_account_id'],
                'description'       => $data['description'] ?? null,
                'is_active'         => (bool) ($data['is_active'] ?? true),
            ]);

            return $rule;
        });
    }

    /**
     * Update an existing auto-posting rule.
     *
     * @param array<string, mixed> $data
     */
    public function updateAutoPostingRule(int $id, array $data): AutoPostingRule
    {
        return DB::transaction(function () use ($id, $data): AutoPostingRule {
            /** @var AutoPostingRule $rule */
            $rule = AutoPostingRule::findOrFail($id);
            $rule->update($data);

            return $rule->fresh();
        });
    }

    /**
     * Delete an auto-posting rule by ID.
     */
    public function deleteAutoPostingRule(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            /** @var AutoPostingRule $rule */
            $rule = AutoPostingRule::findOrFail($id);

            return (bool) $rule->delete();
        });
    }
}
