<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountCategory;
use Modules\Finance\Models\FinanceAccountType;

final class FinanceSeeder extends Seeder
{
    use ResolvesSeedContext;

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
            $types = $this->seedTypes((int) $tenant->getKey());
            $categories = $this->seedCategories((int) $tenant->getKey(), $types);
            $this->seedAccounts(
                (int) $tenant->getKey(),
                $organizationUnit?->getKey(),
                $types,
                $categories,
            );
        }, 3);
    }

    /**
     * @return array<string,FinanceAccountType>
     */
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
     * @param  array<string,FinanceAccountType>  $types
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
     * @param  array<string,FinanceAccountType>  $types
     * @param  array<string,FinanceAccountCategory>  $categories
     */
    private function seedAccounts(int $tenantId, ?int $organizationUnitId, array $types, array $categories): void
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
                    'opening_balance' => '0.000000',
                    'current_balance' => '0.000000',
                    'metadata' => ['seed_source' => 'finance_module'],
                ],
            );
        }

        $accounts = [
            ['1010', 'Cash', 'ASSET', 'CASH', true, false, false],
            ['1020', 'Bank', 'ASSET', 'BANK', false, true, false],
            ['1100', 'Accounts Receivable', 'ASSET', 'AR', false, false, false],
            ['1200', 'Inventory', 'ASSET', 'INVENTORY', false, false, false],
            ['1300', 'Tax Receivable', 'ASSET', 'TAX_RECEIVABLE', false, false, true],
            ['2100', 'Accounts Payable', 'LIABILITY', 'AP', false, false, false],
            ['2200', 'Tax Payable', 'LIABILITY', 'TAX_PAYABLE', false, false, true],
            ['4100', 'Sales Revenue', 'REVENUE', 'SALES', false, false, false],
            ['4200', 'Service Revenue', 'REVENUE', 'SERVICE', false, false, false],
            ['5100', 'Purchase Expense', 'EXPENSE', 'PURCHASE', false, false, false],
            ['5200', 'Cost of Goods Sold', 'EXPENSE', 'COGS', false, false, false],
        ];

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
                    'is_control_account' => in_array($categoryCode, ['AR', 'AP', 'INVENTORY'], true),
                    'is_posting_account' => true,
                    'is_cash_account' => $isCash,
                    'is_bank_account' => $isBank,
                    'is_tax_account' => $isTax,
                    'is_system' => true,
                    'is_active' => true,
                    'opening_balance' => '0.000000',
                    'current_balance' => '0.000000',
                    'metadata' => ['seed_source' => 'finance_module'],
                ],
            );
        }
    }
}
