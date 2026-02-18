<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Entities\Account;
use Modules\Accounting\Entities\FiscalPeriod;

/**
 * Accounting Seeder
 *
 * Seeds sample chart of accounts and fiscal periods.
 */
class AccountingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFiscalPeriods();
        $this->seedChartOfAccounts();
    }

    /**
     * Seed fiscal periods.
     */
    protected function seedFiscalPeriods(): void
    {
        $currentYear = date('Y');

        FiscalPeriod::create([
            'tenant_id' => 1,
            'name' => 'FY '.$currentYear,
            'period_type' => 'year',
            'fiscal_year' => $currentYear,
            'start_date' => $currentYear.'-01-01',
            'end_date' => $currentYear.'-12-31',
            'status' => 'open',
        ]);
    }

    /**
     * Seed chart of accounts.
     */
    protected function seedChartOfAccounts(): void
    {
        $accounts = [
            // ASSETS (1000-1999)
            [
                'account_number' => '1000',
                'name' => 'Assets',
                'type' => 'asset',
                'sub_type' => 'header',
                'is_system' => true,
                'allow_manual_entries' => false,
            ],
            [
                'account_number' => '1100',
                'name' => 'Current Assets',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_number' => '1000',
            ],
            [
                'account_number' => '1110',
                'name' => 'Cash',
                'type' => 'asset',
                'sub_type' => 'cash',
                'parent_number' => '1100',
                'is_system' => true,
            ],
            [
                'account_number' => '1120',
                'name' => 'Bank Account',
                'type' => 'asset',
                'sub_type' => 'bank',
                'parent_number' => '1100',
            ],
            [
                'account_number' => '1130',
                'name' => 'Accounts Receivable',
                'type' => 'asset',
                'sub_type' => 'accounts_receivable',
                'parent_number' => '1100',
                'is_system' => true,
            ],
            [
                'account_number' => '1140',
                'name' => 'Inventory',
                'type' => 'asset',
                'sub_type' => 'inventory',
                'parent_number' => '1100',
            ],
            [
                'account_number' => '1200',
                'name' => 'Fixed Assets',
                'type' => 'asset',
                'sub_type' => 'fixed_asset',
                'parent_number' => '1000',
            ],
            [
                'account_number' => '1210',
                'name' => 'Property, Plant & Equipment',
                'type' => 'asset',
                'sub_type' => 'ppe',
                'parent_number' => '1200',
            ],
            [
                'account_number' => '1220',
                'name' => 'Accumulated Depreciation',
                'type' => 'asset',
                'sub_type' => 'accumulated_depreciation',
                'parent_number' => '1200',
            ],

            // LIABILITIES (2000-2999)
            [
                'account_number' => '2000',
                'name' => 'Liabilities',
                'type' => 'liability',
                'sub_type' => 'header',
                'is_system' => true,
                'allow_manual_entries' => false,
            ],
            [
                'account_number' => '2100',
                'name' => 'Current Liabilities',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'parent_number' => '2000',
            ],
            [
                'account_number' => '2110',
                'name' => 'Accounts Payable',
                'type' => 'liability',
                'sub_type' => 'accounts_payable',
                'parent_number' => '2100',
                'is_system' => true,
            ],
            [
                'account_number' => '2120',
                'name' => 'Tax Payable',
                'type' => 'liability',
                'sub_type' => 'tax_payable',
                'parent_number' => '2100',
            ],
            [
                'account_number' => '2200',
                'name' => 'Long-term Liabilities',
                'type' => 'liability',
                'sub_type' => 'long_term_liability',
                'parent_number' => '2000',
            ],

            // EQUITY (3000-3999)
            [
                'account_number' => '3000',
                'name' => 'Equity',
                'type' => 'equity',
                'sub_type' => 'header',
                'is_system' => true,
                'allow_manual_entries' => false,
            ],
            [
                'account_number' => '3100',
                'name' => 'Owner\'s Equity',
                'type' => 'equity',
                'sub_type' => 'owners_equity',
                'parent_number' => '3000',
            ],
            [
                'account_number' => '3200',
                'name' => 'Retained Earnings',
                'type' => 'equity',
                'sub_type' => 'retained_earnings',
                'parent_number' => '3000',
                'is_system' => true,
            ],

            // REVENUE (4000-4999)
            [
                'account_number' => '4000',
                'name' => 'Revenue',
                'type' => 'revenue',
                'sub_type' => 'header',
                'is_system' => true,
                'allow_manual_entries' => false,
            ],
            [
                'account_number' => '4100',
                'name' => 'Sales Revenue',
                'type' => 'revenue',
                'sub_type' => 'sales',
                'parent_number' => '4000',
            ],
            [
                'account_number' => '4200',
                'name' => 'Service Revenue',
                'type' => 'revenue',
                'sub_type' => 'service',
                'parent_number' => '4000',
            ],
            [
                'account_number' => '4300',
                'name' => 'Other Revenue',
                'type' => 'revenue',
                'sub_type' => 'other',
                'parent_number' => '4000',
            ],

            // EXPENSES (5000-5999)
            [
                'account_number' => '5000',
                'name' => 'Expenses',
                'type' => 'expense',
                'sub_type' => 'header',
                'is_system' => true,
                'allow_manual_entries' => false,
            ],
            [
                'account_number' => '5100',
                'name' => 'Cost of Goods Sold',
                'type' => 'expense',
                'sub_type' => 'cogs',
                'parent_number' => '5000',
            ],
            [
                'account_number' => '5200',
                'name' => 'Operating Expenses',
                'type' => 'expense',
                'sub_type' => 'operating',
                'parent_number' => '5000',
            ],
            [
                'account_number' => '5210',
                'name' => 'Salaries & Wages',
                'type' => 'expense',
                'sub_type' => 'salaries',
                'parent_number' => '5200',
            ],
            [
                'account_number' => '5220',
                'name' => 'Rent Expense',
                'type' => 'expense',
                'sub_type' => 'rent',
                'parent_number' => '5200',
            ],
            [
                'account_number' => '5230',
                'name' => 'Utilities Expense',
                'type' => 'expense',
                'sub_type' => 'utilities',
                'parent_number' => '5200',
            ],
            [
                'account_number' => '5240',
                'name' => 'Marketing & Advertising',
                'type' => 'expense',
                'sub_type' => 'marketing',
                'parent_number' => '5200',
            ],
            [
                'account_number' => '5250',
                'name' => 'Office Supplies',
                'type' => 'expense',
                'sub_type' => 'supplies',
                'parent_number' => '5200',
            ],
        ];

        // Create accounts with proper parent relationships
        $accountMap = [];

        foreach ($accounts as $accountData) {
            $parentNumber = $accountData['parent_number'] ?? null;
            unset($accountData['parent_number']);

            $accountData['tenant_id'] = 1;
            $accountData['currency'] = 'USD';
            $accountData['is_active'] = true;
            $accountData['is_system'] = $accountData['is_system'] ?? false;
            $accountData['allow_manual_entries'] = $accountData['allow_manual_entries'] ?? true;
            $accountData['balance'] = 0;

            if ($parentNumber && isset($accountMap[$parentNumber])) {
                $accountData['parent_id'] = $accountMap[$parentNumber];
            }

            $account = Account::create($accountData);
            $accountMap[$accountData['account_number']] = $account->id;
        }
    }
}
