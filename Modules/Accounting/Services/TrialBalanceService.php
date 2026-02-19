<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Repositories\FiscalPeriodRepository;
use Modules\Core\Helpers\MathHelper;

/**
 * Trial Balance Service
 *
 * Generates trial balance reports
 */
class TrialBalanceService
{
    public function __construct(
        private FiscalPeriodRepository $fiscalPeriodRepository
    ) {}

    /**
     * Generate trial balance report
     */
    public function generateTrialBalance(
        string $organizationId,
        ?string $startDate = null,
        ?string $endDate = null,
        bool $includeInactive = false
    ): array {
        $scale = config('accounting.decimal_scale', 6);
        $includeZeroBalances = config('accounting.include_zero_balances', false);

        $query = Account::where('organization_id', $organizationId)
            ->whereDoesntHave('children');

        if (! $includeInactive) {
            $query->where('status', 'active');
        }

        $accounts = $query->orderBy('code')->get();

        $balances = [];
        $totalDebits = '0';
        $totalCredits = '0';

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalance($account, $startDate, $endDate);

            if (! $includeZeroBalances && MathHelper::equals($balance['debit'], '0', $scale) && MathHelper::equals($balance['credit'], '0', $scale)) {
                continue;
            }

            $balances[] = [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->type->value,
                'debit' => $balance['debit'],
                'credit' => $balance['credit'],
            ];

            $totalDebits = MathHelper::add($totalDebits, $balance['debit'], $scale);
            $totalCredits = MathHelper::add($totalCredits, $balance['credit'], $scale);
        }

        return [
            'organization_id' => $organizationId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'generated_at' => now()->toIso8601String(),
            'balances' => $balances,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => MathHelper::equals($totalDebits, $totalCredits, $scale),
        ];
    }

    /**
     * Generate trial balance by account type
     */
    public function generateTrialBalanceByType(
        string $organizationId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $trialBalance = $this->generateTrialBalance($organizationId, $startDate, $endDate);

        $byType = [];
        foreach (AccountType::cases() as $type) {
            $byType[$type->value] = [
                'label' => $type->value,
                'accounts' => [],
                'total_debit' => '0',
                'total_credit' => '0',
            ];
        }

        $scale = config('accounting.decimal_scale', 6);

        foreach ($trialBalance['balances'] as $balance) {
            $type = $balance['account_type'];
            $byType[$type]['accounts'][] = $balance;
            $byType[$type]['total_debit'] = MathHelper::add($byType[$type]['total_debit'], $balance['debit'], $scale);
            $byType[$type]['total_credit'] = MathHelper::add($byType[$type]['total_credit'], $balance['credit'], $scale);
        }

        $byType = array_filter($byType, fn ($group) => count($group['accounts']) > 0);

        return [
            'organization_id' => $organizationId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'generated_at' => now()->toIso8601String(),
            'by_type' => array_values($byType),
            'total_debits' => $trialBalance['total_debits'],
            'total_credits' => $trialBalance['total_credits'],
            'is_balanced' => $trialBalance['is_balanced'],
        ];
    }

    /**
     * Calculate account balance
     */
    protected function calculateAccountBalance(Account $account, ?string $startDate, ?string $endDate): array
    {
        $scale = config('accounting.decimal_scale', 6);

        $query = $account->journalLines()
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted');

                if ($startDate) {
                    $q->where('entry_date', '>=', $startDate);
                }

                if ($endDate) {
                    $q->where('entry_date', '<=', $endDate);
                }
            });

        $totalDebits = (string) $query->sum('debit');
        $totalCredits = (string) $query->sum('credit');

        if ($account->normal_balance === 'debit') {
            $netBalance = MathHelper::subtract($totalDebits, $totalCredits, $scale);

            if (MathHelper::greaterThan($netBalance, '0', $scale)) {
                return ['debit' => $netBalance, 'credit' => '0'];
            } else {
                return ['debit' => '0', 'credit' => MathHelper::abs($netBalance, $scale)];
            }
        } else {
            $netBalance = MathHelper::subtract($totalCredits, $totalDebits, $scale);

            if (MathHelper::greaterThan($netBalance, '0', $scale)) {
                return ['debit' => '0', 'credit' => $netBalance];
            } else {
                return ['debit' => MathHelper::abs($netBalance, $scale), 'credit' => '0'];
            }
        }
    }
}
