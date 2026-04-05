<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\GenerateFinancialReportServiceInterface;
use Modules\Accounting\Application\DTOs\FinancialReportData;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class GenerateFinancialReportService implements GenerateFinancialReportServiceInterface
{
    private const PAGE_SIZE = 1000;

    public function __construct(
        private readonly AccountRepositoryInterface $accountRepo,
    ) {}

    public function balanceSheet(int $tenantId, \DateTimeInterface $asOf): FinancialReportData
    {
        $accounts = $this->accountRepo->findByTenant($tenantId, self::PAGE_SIZE, 1);
        $items    = method_exists($accounts, 'items') ? $accounts->items() : (array)$accounts;

        $sections = ['assets' => [], 'liabilities' => [], 'equity' => []];
        $totals   = ['assets' => 0.0, 'liabilities' => 0.0, 'equity' => 0.0];

        foreach ($items as $account) {
            $type = $account->getType();
            if ($type === 'asset') {
                $sections['assets'][] = ['name' => $account->getName(), 'code' => $account->getCode(), 'balance' => $account->getBalance()];
                $totals['assets'] += $account->getBalance();
            } elseif ($type === 'liability') {
                $sections['liabilities'][] = ['name' => $account->getName(), 'code' => $account->getCode(), 'balance' => $account->getBalance()];
                $totals['liabilities'] += $account->getBalance();
            } elseif ($type === 'equity') {
                $sections['equity'][] = ['name' => $account->getName(), 'code' => $account->getCode(), 'balance' => $account->getBalance()];
                $totals['equity'] += $account->getBalance();
            }
        }

        return new FinancialReportData(
            reportType:       'balance_sheet',
            generatedAt:      new \DateTimeImmutable(),
            sections:         $sections,
            totalAssets:      $totals['assets'],
            totalLiabilities: $totals['liabilities'],
            totalEquity:      $totals['equity'],
        );
    }

    public function profitAndLoss(int $tenantId, \DateTimeInterface $from, \DateTimeInterface $to): FinancialReportData
    {
        $accounts = $this->accountRepo->findByTenant($tenantId, self::PAGE_SIZE, 1);
        $items    = method_exists($accounts, 'items') ? $accounts->items() : (array)$accounts;

        $sections = ['revenue' => [], 'expenses' => []];
        $totals   = ['revenue' => 0.0, 'expenses' => 0.0];

        foreach ($items as $account) {
            $type = $account->getType();
            if ($type === 'revenue') {
                $sections['revenue'][] = ['name' => $account->getName(), 'code' => $account->getCode(), 'balance' => $account->getBalance()];
                $totals['revenue'] += $account->getBalance();
            } elseif ($type === 'expense') {
                $sections['expenses'][] = ['name' => $account->getName(), 'code' => $account->getCode(), 'balance' => $account->getBalance()];
                $totals['expenses'] += $account->getBalance();
            }
        }

        $netIncome = $totals['revenue'] - $totals['expenses'];

        return new FinancialReportData(
            reportType:    'profit_loss',
            generatedAt:   new \DateTimeImmutable(),
            sections:      $sections,
            totalRevenue:  $totals['revenue'],
            totalExpenses: $totals['expenses'],
            netIncome:     $netIncome,
        );
    }
}
