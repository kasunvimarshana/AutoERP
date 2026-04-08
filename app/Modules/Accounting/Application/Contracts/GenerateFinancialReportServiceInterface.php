<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
interface GenerateFinancialReportServiceInterface {
    public function generateBalanceSheet(string $tenantId, string $asOfDate): array;
    public function generateProfitAndLoss(string $tenantId, string $fromDate, string $toDate): array;
    public function generateCashFlow(string $tenantId, string $fromDate, string $toDate): array;
}
