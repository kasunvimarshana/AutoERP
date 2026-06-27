<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Constants\AccountRoleCode;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountAssignment;
use Modules\Finance\Models\FinanceAccountCategory;
use Modules\Finance\Models\FinanceAccountRole;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceFiscalPeriod;
use Modules\Finance\Models\FinanceFiscalYear;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;
use Modules\Finance\Services\AccountAssignmentService;

final class FinanceSeeder extends Seeder
{
    use ResolvesSeedContext;

    private const DEFAULT_EFFECTIVE_FROM = '1900-01-01';

    public function run(): void
    {
        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $tenantId = (int) $tenant->getKey();
            $organizationUnitId = $organizationUnit?->getKey();
            $types = $this->seedTypes($tenantId);
            $categories = $this->seedCategories($tenantId, $types);
            $accounts = $this->seedAccounts($tenantId, null, $types, $categories);
            $roles = $this->seedRoles($tenantId);
            $this->seedAssignments($tenantId, null, $roles, $accounts);
            $this->seedFiscalCalendar($tenantId, $organizationUnitId);
            $this->seedPostingProfiles($tenantId, null, $roles);
        }, 3);
    }

    /** @return array<string,FinanceAccountType> */
    private function seedTypes(int $tenantId): array
    {
        $definitions = [
            'ASSET' => ['Asset', 'debit', 'balance_sheet'],
            'LIABILITY' => ['Liability', 'credit', 'balance_sheet'],
            'EQUITY' => ['Equity', 'credit', 'balance_sheet'],
            'REVENUE' => ['Revenue', 'credit', 'income_statement'],
            'EXPENSE' => ['Expense', 'debit', 'income_statement'],
        ];

        $types = [];
        foreach ($definitions as $code => [$name, $normalBalance, $statementType]) {
            $types[$code] = FinanceAccountType::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'name' => $name,
                    'normal_balance' => $normalBalance,
                    'statement_type' => $statementType,
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => count($types) + 1,
                ],
            );
        }

        return $types;
    }

    /**
     * @param array<string,FinanceAccountType> $types
     * @return array<string,FinanceAccountCategory>
     */
    private function seedCategories(int $tenantId, array $types): array
    {
        $definitions = [
            'CASH' => ['Cash', 'ASSET'],
            'BANK' => ['Bank', 'ASSET'],
            'AR' => ['Accounts Receivable', 'ASSET'],
            'AP' => ['Accounts Payable', 'LIABILITY'],
            'INVENTORY' => ['Inventory', 'ASSET'],
            'SALES' => ['Sales Revenue', 'REVENUE'],
            'SERVICE' => ['Service Revenue', 'REVENUE'],
            'PURCHASE' => ['Purchase Expense', 'EXPENSE'],
            'COGS' => ['Cost of Goods Sold', 'EXPENSE'],
            'TAX_PAYABLE' => ['Tax Payable', 'LIABILITY'],
            'TAX_RECEIVABLE' => ['Tax Receivable', 'ASSET'],
        ];

        $categories = [];
        foreach ($definitions as $code => [$name, $typeCode]) {
            $categories[$code] = FinanceAccountCategory::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'account_type_id' => $types[$typeCode]->getKey(),
                    'name' => $name,
                    'description' => 'Default AutoERP account category.',
                    'is_active' => true,
                    'sort_order' => count($categories) + 1,
                ],
            );
        }

        return $categories;
    }

    /**
     * @param array<string,FinanceAccountType> $types
     * @param array<string,FinanceAccountCategory> $categories
     * @return array<string,FinanceAccount>
     */
    private function seedAccounts(int $tenantId, ?int $organizationUnitId, array $types, array $categories): array
    {
        $rootDefinitions = [
            '1000' => ['Asset', 'ASSET'],
            '2000' => ['Liability', 'LIABILITY'],
            '3000' => ['Equity', 'EQUITY'],
            '4000' => ['Revenue', 'REVENUE'],
            '5000' => ['Expense', 'EXPENSE'],
        ];

        $roots = [];
        foreach ($rootDefinitions as $code => [$name, $typeCode]) {
            $roots[$typeCode] = FinanceAccount::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'account_type_id' => $types[$typeCode]->getKey(),
                    'account_category_id' => null,
                    'parent_id' => null,
                    'name' => $name,
                    'normal_balance' => $types[$typeCode]->normal_balance->value,
                    'is_control_account' => true,
                    'is_posting_account' => false,
                    'is_system' => true,
                    'is_active' => true,
                    'metadata' => ['seed_source' => 'finance_module'],
                ],
            );
        }

        $definitions = [
            ['1010', 'Cash', 'ASSET', 'CASH', true, false, false],
            ['1020', 'Bank', 'ASSET', 'BANK', false, true, false],
            ['1100', 'Accounts Receivable', 'ASSET', 'AR', false, false, false],
            ['1200', 'Inventory', 'ASSET', 'INVENTORY', false, false, false],
            ['1210', 'Vehicle Service Work in Progress', 'ASSET', 'INVENTORY', false, false, false],
            ['1300', 'Tax Receivable', 'ASSET', 'TAX_RECEIVABLE', false, false, true],
            ['1310', 'Withholding Tax Receivable', 'ASSET', 'TAX_RECEIVABLE', false, false, true],
            ['2100', 'Accounts Payable', 'LIABILITY', 'AP', false, false, false],
            ['2110', 'Vehicle Owner Payable', 'LIABILITY', 'AP', false, false, false],
            ['2200', 'Tax Payable', 'LIABILITY', 'TAX_PAYABLE', false, false, true],
            ['4100', 'Sales Revenue', 'REVENUE', 'SALES', false, false, false],
            ['4200', 'Service Revenue', 'REVENUE', 'SERVICE', false, false, false],
            ['4210', 'Material Revenue', 'REVENUE', 'SERVICE', false, false, false],
            ['4220', 'Labour Revenue', 'REVENUE', 'SERVICE', false, false, false],
            ['4230', 'External Work Revenue', 'REVENUE', 'SERVICE', false, false, false],
            ['4300', 'Vehicle Rental Income', 'REVENUE', 'SERVICE', false, false, false],
            ['4310', 'Excess Kilometre Income', 'REVENUE', 'SERVICE', false, false, false],
            ['4320', 'Driver Reimbursement Income', 'REVENUE', 'SERVICE', false, false, false],
            ['4330', 'Fuel and Repair Recovery', 'REVENUE', 'SERVICE', false, false, false],
            ['5100', 'Purchase Expense', 'EXPENSE', 'PURCHASE', false, false, false],
            ['5200', 'Cost of Goods Sold', 'EXPENSE', 'COGS', false, false, false],
            ['5210', 'Vehicle Service Material Cost', 'EXPENSE', 'COGS', false, false, false],
            ['5300', 'Vehicle Rental Direct Cost', 'EXPENSE', 'PURCHASE', false, false, false],
            ['5310', 'Excess Kilometre Expense', 'EXPENSE', 'PURCHASE', false, false, false],
        ];

        $accounts = $roots;
        foreach ($definitions as [$code, $name, $typeCode, $categoryCode, $isCash, $isBank, $isTax]) {
            $accounts[$code] = FinanceAccount::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'account_type_id' => $types[$typeCode]->getKey(),
                    'account_category_id' => $categories[$categoryCode]->getKey(),
                    'parent_id' => $roots[$typeCode]->getKey(),
                    'name' => $name,
                    'normal_balance' => $types[$typeCode]->normal_balance->value,
                    'is_control_account' => in_array($categoryCode, ['AR', 'AP', 'INVENTORY'], true),
                    'is_posting_account' => true,
                    'is_cash_account' => $isCash,
                    'is_bank_account' => $isBank,
                    'is_tax_account' => $isTax,
                    'is_system' => true,
                    'is_active' => true,
                    'metadata' => ['seed_source' => 'finance_module'],
                ],
            );
        }

        return $accounts;
    }

    /** @return array<string,FinanceAccountRole> */
    private function seedRoles(int $tenantId): array
    {
        $roles = [];
        foreach (AccountRoleCode::definitions() as $code => $definition) {
            $roles[$code] = FinanceAccountRole::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'owning_module' => $definition['module'],
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_active' => true,
                ],
            );
        }

        return $roles;
    }

    /**
     * @param array<string,FinanceAccountRole> $roles
     * @param array<string,FinanceAccount> $accounts
     */
    private function seedAssignments(
        int $tenantId,
        ?int $organizationUnitId,
        array $roles,
        array $accounts,
    ): void {
        $accountByRole = [
            AccountRoleCode::CASH => '1010',
            AccountRoleCode::BANK => '1020',
            AccountRoleCode::ACCOUNTS_RECEIVABLE => '1100',
            AccountRoleCode::ACCOUNTS_PAYABLE => '2100',
            AccountRoleCode::INVENTORY_ASSET => '1200',
            AccountRoleCode::SALES_REVENUE => '4100',
            AccountRoleCode::SERVICE_REVENUE => '4200',
            AccountRoleCode::PURCHASE_EXPENSE => '5100',
            AccountRoleCode::COST_OF_GOODS_SOLD => '5200',
            AccountRoleCode::INPUT_TAX => '1300',
            AccountRoleCode::OUTPUT_TAX => '2200',
            AccountRoleCode::WITHHOLDING_RECEIVABLE => '1310',
            AccountRoleCode::WITHHOLDING_PAYABLE => '2200',
            AccountRoleCode::PAYMENT_RECEIPT_ACCOUNT => '1010',
            AccountRoleCode::PAYMENT_DISBURSEMENT_ACCOUNT => '1020',
            AccountRoleCode::PURCHASE_ADJUSTMENT => '5100',
            AccountRoleCode::CURRENCY_EXPOSURE => '1100',
            AccountRoleCode::UNREALIZED_GAIN => '4100',
            AccountRoleCode::UNREALIZED_LOSS => '5100',
            AccountRoleCode::VEHICLE_SERVICE_WIP => '1210',
            AccountRoleCode::MATERIAL_REVENUE => '4210',
            AccountRoleCode::MATERIAL_COST_OF_SALES => '5210',
            AccountRoleCode::LABOUR_REVENUE => '4220',
            AccountRoleCode::EXTERNAL_WORK_REVENUE => '4230',
            AccountRoleCode::RENTAL_INCOME => '4300',
            AccountRoleCode::EXCESS_KM_INCOME => '4310',
            AccountRoleCode::DRIVER_REIMBURSEMENT_INCOME => '4320',
            AccountRoleCode::RENTAL_DIRECT_COST => '5300',
            AccountRoleCode::EXCESS_KM_EXPENSE => '5310',
            AccountRoleCode::VEHICLE_OWNER_PAYABLE => '2110',
            AccountRoleCode::FUEL_REPAIR_RECOVERY => '4330',
        ];
        $scopeService = app(AccountAssignmentService::class);

        foreach ($accountByRole as $roleCode => $accountCode) {
            $role = $roles[$roleCode];
            $account = $accounts[$accountCode];
            $scopeKey = $scopeService->scopeKey($organizationUnitId, $roleCode, null, null);
            FinanceAccountAssignment::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'scope_key' => $scopeKey,
                    'effective_from' => self::DEFAULT_EFFECTIVE_FROM,
                ],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'account_role_id' => $role->getKey(),
                    'account_id' => $account->getKey(),
                    'context_type' => null,
                    'context_id' => null,
                    'effective_to' => null,
                    'is_active' => true,
                    'description' => 'Default AutoERP account assignment.',
                ],
            );
        }
    }

    private function seedFiscalCalendar(int $tenantId, ?int $organizationUnitId): void
    {
        $yearNumber = (int) now()->year;
        $start = CarbonImmutable::create($yearNumber, 1, 1);
        $year = FinanceFiscalYear::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'start_date' => $start,
                'end_date' => $start->endOfYear(),
            ],
            ['name' => 'FY '.$yearNumber, 'status' => 'open'],
        );

        for ($month = 1; $month <= 12; $month++) {
            $periodStart = CarbonImmutable::create($yearNumber, $month, 1);
            FinanceFiscalPeriod::query()->updateOrCreate(
                ['fiscal_year_id' => $year->getKey(), 'period_number' => $month],
                [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $periodStart->format('F Y'),
                    'start_date' => $periodStart->toDateString(),
                    'end_date' => $periodStart->endOfMonth()->toDateString(),
                    'status' => 'open',
                ],
            );
        }
    }

    /** @param array<string,FinanceAccountRole> $roles */
    private function seedPostingProfiles(int $tenantId, ?int $organizationUnitId, array $roles): void
    {
        $definitions = [
            'sales_invoice' => ['name' => 'Sales Invoice', 'rules' => [
                'receivable' => AccountRoleCode::ACCOUNTS_RECEIVABLE,
                'revenue' => AccountRoleCode::SALES_REVENUE,
                'tax_payable' => AccountRoleCode::OUTPUT_TAX,
                'withholding_receivable' => AccountRoleCode::WITHHOLDING_RECEIVABLE,
            ]],
            'purchase_invoice' => ['name' => 'Purchase Invoice', 'rules' => [
                'expense' => AccountRoleCode::PURCHASE_EXPENSE,
                'payable' => AccountRoleCode::ACCOUNTS_PAYABLE,
                'tax_receivable' => AccountRoleCode::INPUT_TAX,
                'withholding_payable' => AccountRoleCode::WITHHOLDING_PAYABLE,
                'adjustment' => AccountRoleCode::PURCHASE_ADJUSTMENT,
            ]],
            'payment_received' => ['name' => 'Payment Received', 'rules' => [
                'settlement' => AccountRoleCode::PAYMENT_RECEIPT_ACCOUNT,
                'receivable' => AccountRoleCode::ACCOUNTS_RECEIVABLE,
            ]],
            'payment_made' => ['name' => 'Payment Made', 'rules' => [
                'settlement' => AccountRoleCode::PAYMENT_DISBURSEMENT_ACCOUNT,
                'payable' => AccountRoleCode::ACCOUNTS_PAYABLE,
            ]],
            'inventory_receipt' => ['name' => 'Inventory Receipt', 'rules' => [
                'inventory' => AccountRoleCode::INVENTORY_ASSET,
                'payable' => AccountRoleCode::ACCOUNTS_PAYABLE,
            ]],
            'inventory_issue' => ['name' => 'Inventory Issue', 'rules' => [
                'cost_of_goods_sold' => AccountRoleCode::COST_OF_GOODS_SOLD,
                'inventory' => AccountRoleCode::INVENTORY_ASSET,
            ]],
            'vehicle_service_invoice' => ['name' => 'Vehicle Service Invoice', 'rules' => [
                'receivable' => AccountRoleCode::ACCOUNTS_RECEIVABLE,
                'service_revenue' => AccountRoleCode::SERVICE_REVENUE,
                'material_revenue' => AccountRoleCode::MATERIAL_REVENUE,
                'labour_revenue' => AccountRoleCode::LABOUR_REVENUE,
                'external_work_revenue' => AccountRoleCode::EXTERNAL_WORK_REVENUE,
                'tax_payable' => AccountRoleCode::OUTPUT_TAX,
            ]],
            'sales_return' => ['name' => 'Sales Return', 'rules' => [
                'sales_revenue' => AccountRoleCode::SALES_REVENUE,
                'receivable' => AccountRoleCode::ACCOUNTS_RECEIVABLE,
            ]],
            'purchase_return' => ['name' => 'Purchase Return', 'rules' => [
                'payable' => AccountRoleCode::ACCOUNTS_PAYABLE,
                'purchase_expense' => AccountRoleCode::PURCHASE_EXPENSE,
            ]],
            'currency_revaluation' => ['name' => 'Currency Revaluation', 'rules' => [
                'exposure' => AccountRoleCode::CURRENCY_EXPOSURE,
                'unrealized_gain' => AccountRoleCode::UNREALIZED_GAIN,
                'unrealized_loss' => AccountRoleCode::UNREALIZED_LOSS,
            ]],
        ];
        $scopeKey = $organizationUnitId === null ? 'global' : 'ou:'.$organizationUnitId;

        foreach ($definitions as $code => $definition) {
            $profile = FinancePostingProfile::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'scope_key' => $scopeKey, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $definition['name'],
                    'description' => 'Default AutoERP posting profile.',
                    'is_active' => true,
                ],
            );

            foreach ($definition['rules'] as $lineKey => $roleCode) {
                FinancePostingProfileRule::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'posting_profile_id' => $profile->getKey(),
                        'line_key' => $lineKey,
                    ],
                    [
                        'account_role_id' => $roles[$roleCode]->getKey(),
                        'description' => $definition['name'].' '.$lineKey,
                    ],
                );
            }
        }
    }
}
