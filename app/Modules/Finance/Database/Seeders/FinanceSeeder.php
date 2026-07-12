<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountAssignment;
use Modules\Finance\Models\FinanceAccountCategory;
use Modules\Finance\Models\FinanceAccountRole;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;

final class FinanceSeeder extends Seeder
{
    use ResolvesSeedContext;

    private const OPENING_EFFECTIVE_DATE = '1900-01-01';

    private const TYPE_ASSET = 'ASSET';
    private const TYPE_LIABILITY = 'LIABILITY';
    private const TYPE_EQUITY = 'EQUITY';
    private const TYPE_REVENUE = 'REVENUE';
    private const TYPE_EXPENSE = 'EXPENSE';

    private const ACCOUNT_CASH = '1010';
    private const ACCOUNT_BANK = '1020';
    private const ACCOUNT_RECEIVABLE = '1100';
    private const ACCOUNT_INVENTORY = '1200';
    private const ACCOUNT_TAX_RECEIVABLE = '1300';
    private const ACCOUNT_SUPPLIER_ADVANCE = '1400';
    private const ACCOUNT_PAYABLE = '2100';
    private const ACCOUNT_TAX_PAYABLE = '2200';
    private const ACCOUNT_CUSTOMER_ADVANCE = '2300';
    private const ACCOUNT_CUSTOMER_DEPOSIT = '2310';
    private const ACCOUNT_SALES_REVENUE = '4100';
    private const ACCOUNT_SERVICE_REVENUE = '4200';
    private const ACCOUNT_PURCHASE_EXPENSE = '5100';
    private const ACCOUNT_COST_OF_GOODS_SOLD = '5200';

    public function run(): void
    {
        if (! Schema::hasTable('finance_accounts')) {
            return;
        }

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
            $this->seedAccounts($tenantId, $organizationUnitId, $types, $categories);
            $this->seedPostingProfiles($tenantId, $organizationUnitId);
        }, 3);
    }

    /** @return array<string, FinanceAccountType> */
    private function seedTypes(int $tenantId): array
    {
        $definitions = [
            self::TYPE_ASSET => ['Asset', 'debit', 'balance_sheet'],
            self::TYPE_LIABILITY => ['Liability', 'credit', 'balance_sheet'],
            self::TYPE_EQUITY => ['Equity', 'credit', 'balance_sheet'],
            self::TYPE_REVENUE => ['Revenue', 'credit', 'income_statement'],
            self::TYPE_EXPENSE => ['Expense', 'debit', 'income_statement'],
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
     * @param  array<string, FinanceAccountType>  $types
     * @return array<string, FinanceAccountCategory>
     */
    private function seedCategories(int $tenantId, array $types): array
    {
        $definitions = [
            'CASH' => ['Cash', self::TYPE_ASSET],
            'BANK' => ['Bank', self::TYPE_ASSET],
            'AR' => ['Accounts Receivable', self::TYPE_ASSET],
            'INVENTORY' => ['Inventory', self::TYPE_ASSET],
            'TAX_RECEIVABLE' => ['Tax Receivable', self::TYPE_ASSET],
            'SUPPLIER_ADVANCE' => ['Supplier Advances', self::TYPE_ASSET],
            'AP' => ['Accounts Payable', self::TYPE_LIABILITY],
            'TAX_PAYABLE' => ['Tax Payable', self::TYPE_LIABILITY],
            'CUSTOMER_ADVANCE' => ['Customer Advances', self::TYPE_LIABILITY],
            'CUSTOMER_DEPOSIT' => ['Customer Deposits', self::TYPE_LIABILITY],
            'SALES' => ['Sales Revenue', self::TYPE_REVENUE],
            'SERVICE' => ['Service Revenue', self::TYPE_REVENUE],
            'PURCHASE' => ['Purchase Expense', self::TYPE_EXPENSE],
            'COGS' => ['Cost of Goods Sold', self::TYPE_EXPENSE],
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
     * @param  array<string, FinanceAccountType>  $types
     * @param  array<string, FinanceAccountCategory>  $categories
     */
    private function seedAccounts(int $tenantId, ?int $organizationUnitId, array $types, array $categories): void
    {
        $rootDefinitions = [
            '1000' => ['Asset', self::TYPE_ASSET],
            '2000' => ['Liability', self::TYPE_LIABILITY],
            '3000' => ['Equity', self::TYPE_EQUITY],
            '4000' => ['Revenue', self::TYPE_REVENUE],
            '5000' => ['Expense', self::TYPE_EXPENSE],
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

        $accounts = [
            [self::ACCOUNT_CASH, 'Cash', self::TYPE_ASSET, 'CASH', true, false, false],
            [self::ACCOUNT_BANK, 'Bank', self::TYPE_ASSET, 'BANK', false, true, false],
            [self::ACCOUNT_RECEIVABLE, 'Accounts Receivable', self::TYPE_ASSET, 'AR', false, false, false],
            [self::ACCOUNT_INVENTORY, 'Inventory', self::TYPE_ASSET, 'INVENTORY', false, false, false],
            [self::ACCOUNT_TAX_RECEIVABLE, 'Tax Receivable', self::TYPE_ASSET, 'TAX_RECEIVABLE', false, false, true],
            [self::ACCOUNT_SUPPLIER_ADVANCE, 'Supplier Advances', self::TYPE_ASSET, 'SUPPLIER_ADVANCE', false, false, false],
            [self::ACCOUNT_PAYABLE, 'Accounts Payable', self::TYPE_LIABILITY, 'AP', false, false, false],
            [self::ACCOUNT_TAX_PAYABLE, 'Tax Payable', self::TYPE_LIABILITY, 'TAX_PAYABLE', false, false, true],
            [self::ACCOUNT_CUSTOMER_ADVANCE, 'Customer Advances', self::TYPE_LIABILITY, 'CUSTOMER_ADVANCE', false, false, false],
            [self::ACCOUNT_CUSTOMER_DEPOSIT, 'Rental Security Deposits', self::TYPE_LIABILITY, 'CUSTOMER_DEPOSIT', false, false, false],
            [self::ACCOUNT_SALES_REVENUE, 'Sales Revenue', self::TYPE_REVENUE, 'SALES', false, false, false],
            [self::ACCOUNT_SERVICE_REVENUE, 'Service Revenue', self::TYPE_REVENUE, 'SERVICE', false, false, false],
            [self::ACCOUNT_PURCHASE_EXPENSE, 'Purchase Expense', self::TYPE_EXPENSE, 'PURCHASE', false, false, false],
            [self::ACCOUNT_COST_OF_GOODS_SOLD, 'Cost of Goods Sold', self::TYPE_EXPENSE, 'COGS', false, false, false],
        ];
        $controlCategories = ['AR', 'AP', 'INVENTORY', 'SUPPLIER_ADVANCE', 'CUSTOMER_ADVANCE', 'CUSTOMER_DEPOSIT'];

        foreach ($accounts as [$code, $name, $typeCode, $categoryCode, $isCash, $isBank, $isTax]) {
            FinanceAccount::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'account_type_id' => $types[$typeCode]->getKey(),
                    'account_category_id' => $categories[$categoryCode]->getKey(),
                    'parent_id' => $roots[$typeCode]->getKey(),
                    'name' => $name,
                    'normal_balance' => $types[$typeCode]->normal_balance->value,
                    'is_control_account' => in_array($categoryCode, $controlCategories, true),
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
    }

    private function seedPostingProfiles(int $tenantId, ?int $organizationUnitId): void
    {
        if (! Schema::hasTable('finance_posting_profiles')
            || ! Schema::hasTable('finance_account_roles')
            || ! Schema::hasTable('finance_account_assignments')) {
            return;
        }

        $definitions = [
            'sales_invoice' => [
                'name' => 'Sales Invoice',
                'rules' => [
                    'receivable' => self::ACCOUNT_RECEIVABLE,
                    'revenue' => self::ACCOUNT_SALES_REVENUE,
                    'tax_payable' => self::ACCOUNT_TAX_PAYABLE,
                    'withholding_receivable' => self::ACCOUNT_TAX_RECEIVABLE,
                ],
            ],
            'purchase_invoice' => [
                'name' => 'Purchase Invoice',
                'rules' => [
                    'expense' => self::ACCOUNT_PURCHASE_EXPENSE,
                    'payable' => self::ACCOUNT_PAYABLE,
                    'tax_receivable' => self::ACCOUNT_TAX_RECEIVABLE,
                    'withholding_payable' => self::ACCOUNT_TAX_PAYABLE,
                ],
            ],
            'customer_receipt' => [
                'name' => 'Customer Receipt',
                'rules' => [
                    'cash' => self::ACCOUNT_CASH,
                    'bank' => self::ACCOUNT_BANK,
                    'receivable' => self::ACCOUNT_RECEIVABLE,
                    'customer_advance' => self::ACCOUNT_CUSTOMER_ADVANCE,
                ],
            ],
            'supplier_payment' => [
                'name' => 'Supplier Payment',
                'rules' => [
                    'cash' => self::ACCOUNT_CASH,
                    'bank' => self::ACCOUNT_BANK,
                    'payable' => self::ACCOUNT_PAYABLE,
                    'supplier_advance' => self::ACCOUNT_SUPPLIER_ADVANCE,
                ],
            ],
            'customer_advance' => [
                'name' => 'Customer Advance Receipt',
                'rules' => [
                    'cash' => self::ACCOUNT_CASH,
                    'bank' => self::ACCOUNT_BANK,
                    'receivable' => self::ACCOUNT_RECEIVABLE,
                    'customer_advance' => self::ACCOUNT_CUSTOMER_ADVANCE,
                ],
            ],
            'supplier_advance' => [
                'name' => 'Supplier Advance Payment',
                'rules' => [
                    'cash' => self::ACCOUNT_CASH,
                    'bank' => self::ACCOUNT_BANK,
                    'payable' => self::ACCOUNT_PAYABLE,
                    'supplier_advance' => self::ACCOUNT_SUPPLIER_ADVANCE,
                ],
            ],
            'rental_deposit' => [
                'name' => 'Rental Security Deposit',
                'rules' => [
                    'cash' => self::ACCOUNT_CASH,
                    'bank' => self::ACCOUNT_BANK,
                    'receivable' => self::ACCOUNT_RECEIVABLE,
                    'customer_deposit' => self::ACCOUNT_CUSTOMER_DEPOSIT,
                ],
            ],
            'inventory_receipt' => [
                'name' => 'Inventory Receipt',
                'rules' => ['inventory' => self::ACCOUNT_INVENTORY, 'payable' => self::ACCOUNT_PAYABLE],
            ],
            'inventory_issue' => [
                'name' => 'Inventory Issue',
                'rules' => ['cost_of_goods_sold' => self::ACCOUNT_COST_OF_GOODS_SOLD, 'inventory' => self::ACCOUNT_INVENTORY],
            ],
            'vehicle_service_invoice' => [
                'name' => 'Vehicle Service Invoice',
                'rules' => ['receivable' => self::ACCOUNT_RECEIVABLE, 'service_revenue' => self::ACCOUNT_SERVICE_REVENUE, 'tax_payable' => self::ACCOUNT_TAX_PAYABLE],
            ],
            'sales_return' => [
                'name' => 'Sales Return',
                'rules' => ['sales_revenue' => self::ACCOUNT_SALES_REVENUE, 'receivable' => self::ACCOUNT_RECEIVABLE],
            ],
            'purchase_return' => [
                'name' => 'Purchase Return',
                'rules' => ['payable' => self::ACCOUNT_PAYABLE, 'purchase_expense' => self::ACCOUNT_PURCHASE_EXPENSE],
            ],
        ];

        $accounts = FinanceAccount::query()->where('tenant_id', $tenantId)->get()->keyBy('code');

        foreach ($definitions as $code => $definition) {
            $profile = FinancePostingProfile::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'code' => $code,
                ],
                [
                    'name' => $definition['name'],
                    'description' => 'Default AutoERP posting profile.',
                    'is_active' => true,
                ],
            );

            foreach ($definition['rules'] as $lineKey => $accountCode) {
                $account = $accounts->get($accountCode);
                if (! $account instanceof FinanceAccount) {
                    continue;
                }

                $role = FinanceAccountRole::query()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $lineKey],
                    [
                        'name' => Str::headline($lineKey),
                        'description' => 'Default AutoERP semantic Finance account role.',
                        'is_active' => true,
                    ],
                );

                $this->seedAccountAssignment($tenantId, $organizationUnitId, $role, $account);
                FinancePostingProfileRule::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'posting_profile_id' => $profile->getKey(),
                        'line_key' => $lineKey,
                        'effective_from' => FinancePostingProfileRule::OPENING_EFFECTIVE_DATE,
                    ],
                    [
                        'account_role_id' => $role->getKey(),
                        'effective_to' => null,
                        'is_active' => true,
                        'description' => $definition['name'].' '.$lineKey,
                    ],
                );
            }
        }
    }

    private function seedAccountAssignment(
        int $tenantId,
        ?int $organizationUnitId,
        FinanceAccountRole $role,
        FinanceAccount $account,
    ): void {
        FinanceAccountAssignment::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'account_role_id' => $role->getKey(),
                'effective_from' => self::OPENING_EFFECTIVE_DATE,
            ],
            [
                'account_id' => $account->getKey(),
                'effective_to' => null,
                'is_active' => true,
            ],
        );

        $duplicates = FinanceAccountAssignment::query()
            ->where('tenant_id', $tenantId)
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->where('account_role_id', $role->getKey())
            ->where('account_id', $account->getKey())
            ->whereDate('effective_from', self::OPENING_EFFECTIVE_DATE)
            ->whereNull('effective_to')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($duplicates->skip(1) as $duplicate) {
            $duplicate->forceFill(['is_active' => false])->save();
        }
    }
}
