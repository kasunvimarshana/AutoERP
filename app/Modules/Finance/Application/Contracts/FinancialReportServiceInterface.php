<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

interface FinancialReportServiceInterface
{
    /**
     * General Ledger: all posted journal entry lines for a tenant/period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generalLedger(int $tenantId, array $filters = []): array;

    /**
     * Trial Balance: debit/credit totals per account for a tenant/period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function trialBalance(int $tenantId, array $filters = []): array;

    /**
     * Balance Sheet: asset, liability, and equity account balances.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function balanceSheet(int $tenantId, array $filters = []): array;

    /**
     * Profit & Loss: revenue and expense account balances for a period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function profitLoss(int $tenantId, array $filters = []): array;
}
