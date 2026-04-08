<?php

declare(strict_types=1);

namespace Modules\Financial\Application\Contracts;

interface FinancialReportingServiceInterface
{
    /**
     * Generate a Balance Sheet as-of the given date.
     *
     * Returns a structured array:
     * [
     *   'as_of_date'   => string,
     *   'assets'       => ['accounts' => [...], 'total' => float],
     *   'liabilities'  => ['accounts' => [...], 'total' => float],
     *   'equity'       => ['accounts' => [...], 'total' => float],
     *   'is_balanced'  => bool,
     * ]
     */
    public function balanceSheet(int $tenantId, string $asOfDate, string $currencyCode = 'USD'): array;

    /**
     * Generate a Profit & Loss (Income Statement) for the given date range.
     *
     * Returns a structured array:
     * [
     *   'from_date'     => string,
     *   'to_date'       => string,
     *   'revenue'       => ['accounts' => [...], 'total' => float],
     *   'expenses'      => ['accounts' => [...], 'total' => float],
     *   'net_income'    => float,
     * ]
     */
    public function profitAndLoss(int $tenantId, string $fromDate, string $toDate, string $currencyCode = 'USD'): array;
}
