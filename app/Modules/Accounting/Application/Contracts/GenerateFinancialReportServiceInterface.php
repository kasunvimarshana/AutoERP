<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Application\DTOs\FinancialReportData;

interface GenerateFinancialReportServiceInterface
{
    public function balanceSheet(int $tenantId, \DateTimeInterface $asOf): FinancialReportData;
    public function profitAndLoss(int $tenantId, \DateTimeInterface $from, \DateTimeInterface $to): FinancialReportData;
}
