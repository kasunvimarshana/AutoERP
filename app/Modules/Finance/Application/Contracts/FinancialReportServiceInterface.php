<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

interface FinancialReportServiceInterface
{
    /**
     * Generate a Balance Sheet report as of the given date.
     *
     * @return array{assets: array, liabilities: array, equity: array, totals: array}
     */
    public function getBalanceSheet(int $tenantId, string $asOfDate): array;

    /**
     * Generate a Profit & Loss (Income Statement) for the given period.
     *
     * @return array{revenue: array, expenses: array, net_income: float, totals: array}
     */
    public function getProfitAndLoss(int $tenantId, string $fromDate, string $toDate): array;

    /**
     * Generate a Trial Balance as of the given date.
     *
     * @return array{accounts: array, total_debit: float, total_credit: float}
     */
    public function getTrialBalance(int $tenantId, string $asOfDate): array;

    /**
     * Generate a Cash Flow Statement for the given period.
     *
     * @return array{operating: array, investing: array, financing: array, net_change: float}
     */
    public function getCashFlowStatement(int $tenantId, string $fromDate, string $toDate): array;
}
