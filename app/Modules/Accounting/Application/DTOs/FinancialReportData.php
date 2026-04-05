<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\DTOs;

class FinancialReportData
{
    public function __construct(
        public readonly string $reportType,
        public readonly \DateTimeInterface $generatedAt,
        public readonly array $sections,
        public readonly float $totalAssets = 0.0,
        public readonly float $totalLiabilities = 0.0,
        public readonly float $totalEquity = 0.0,
        public readonly float $totalRevenue = 0.0,
        public readonly float $totalExpenses = 0.0,
        public readonly float $netIncome = 0.0,
    ) {}

    public function isBalanced(): bool
    {
        return $this->reportType === 'balance_sheet'
            ? abs($this->totalAssets - ($this->totalLiabilities + $this->totalEquity)) < 0.01
            : true;
    }
}
