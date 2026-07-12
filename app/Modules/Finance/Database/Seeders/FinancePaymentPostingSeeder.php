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

final class FinancePaymentPostingSeeder extends Seeder
{
    use ResolvesSeedContext;

    private const OPENING_EFFECTIVE_DATE = '1900-01-01';

    private const ASSET_TYPE_CODE = 'ASSET';
    private const LIABILITY_TYPE_CODE = 'LIABILITY';
    private const ASSET_ROOT_CODE = '1000';
    private const LIABILITY_ROOT_CODE = '2000';

    private const SUPPLIER_ADVANCE_CATEGORY = 'SUPPLIER_ADVANCE';
    private const CUSTOMER_ADVANCE_CATEGORY = 'CUSTOMER_ADVANCE';
    private const CUSTOMER_DEPOSIT_CATEGORY = 'CUSTOMER_DEPOSIT';

    private const SUPPLIER_ADVANCE_ACCOUNT = '1400';
    private const CUSTOMER_ADVANCE_ACCOUNT = '2300';
    private const CUSTOMER_DEPOSIT_ACCOUNT = '2310';
    private const CASH_ACCOUNT = '1010';
    private const BANK_ACCOUNT = '1020';
    private const RECEIVABLE_ACCOUNT = '1100';
    private const PAYABLE_ACCOUNT = '2100';

    public function run(): void
    {
        if (! Schema::hasTable('finance_accounts')
            || ! Schema::hasTable('finance_posting_profiles')
            || ! Schema::hasTable('finance_posting_profile_rules')
            || ! Schema::hasTable('finance_account_roles')
            || ! Schema::hasTable('finance_account_assignments')) {
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
            $accounts = $this->seedControlAccounts($tenantId, $organizationUnitId);
            $this->seedPostingProfiles($tenantId, $organizationUnitId, $accounts);
        }, 3);
    }

    /** @return array<string, FinanceAccount> */
    private function seedControlAccounts(int $tenantId, ?int $organizationUnitId): array
    {
        $assetType = $this->accountType($tenantId, self::ASSET_TYPE_CODE);
        $liabilityType = $this->accountType($tenantId, self::LIABILITY_TYPE_CODE);
        $assetRoot = $this->account($tenantId, self::ASSET_ROOT_CODE);
        $liabilityRoot = $this->account($tenantId, self::LIABILITY_ROOT_CODE);

        $supplierAdvanceCategory = $this->category($tenantId, $assetType, self::SUPPLIER_ADVANCE_CATEGORY, 'Supplier Advances');
        $customerAdvanceCategory = $this->category($tenantId, $liabilityType, self::CUSTOMER_ADVANCE_CATEGORY, 'Customer Advances');
        $customerDepositCategory = $this->category($tenantId, $liabilityType, self::CUSTOMER_DEPOSIT_CATEGORY, 'Customer Deposits');

        $supplierAdvance = $this->postingAccount($tenantId, $organizationUnitId, self::SUPPLIER_ADVANCE_ACCOUNT, 'Supplier Advances', $assetType, $supplierAdvanceCategory, $assetRoot);
        $customerAdvance = $this->postingAccount($tenantId, $organizationUnitId, self::CUSTOMER_ADVANCE_ACCOUNT, 'Customer Advances', $liabilityType, $customerAdvanceCategory, $liabilityRoot);
        $customerDeposit = $this->postingAccount($tenantId, $organizationUnitId, self::CUSTOMER_DEPOSIT_ACCOUNT, 'Rental Security Deposits', $liabilityType, $customerDepositCategory, $liabilityRoot);

        return [
            'cash' => $this->account($tenantId, self::CASH_ACCOUNT),
            'bank' => $this->account($tenantId, self::BANK_ACCOUNT),
            'receivable' => $this->account($tenantId, self::RECEIVABLE_ACCOUNT),
            'payable' => $this->account($tenantId, self::PAYABLE_ACCOUNT),
            'customer_advance' => $customerAdvance,
            'supplier_advance' => $supplierAdvance,
            'customer_deposit' => $customerDeposit,
        ];
    }

    /** @param array<string, FinanceAccount> $accounts */
    private function seedPostingProfiles(int $tenantId, ?int $organizationUnitId, array $accounts): void
    {
        $definitions = [
            'payment_received' => ['name' => 'Payment Received', 'roles' => ['cash', 'bank', 'receivable', 'customer_advance']],
            'payment_made' => ['name' => 'Payment Made', 'roles' => ['cash', 'bank', 'payable', 'supplier_advance']],
            'customer_advance' => ['name' => 'Customer Advance Receipt', 'roles' => ['cash', 'bank', 'receivable', 'customer_advance']],
            'supplier_advance' => ['name' => 'Supplier Advance Payment', 'roles' => ['cash', 'bank', 'payable', 'supplier_advance']],
            'rental_deposit' => ['name' => 'Rental Security Deposit', 'roles' => ['cash', 'bank', 'receivable', 'customer_deposit']],
        ];

        foreach ($definitions as $profileCode => $definition) {
            $profile = FinancePostingProfile::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId, 'code' => $profileCode],
                ['name' => $definition['name'], 'description' => 'Default semantic payment posting profile.', 'is_active' => true],
            );

            foreach ($definition['roles'] as $roleCode) {
                $account = $accounts[$roleCode];
                $role = FinanceAccountRole::query()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $roleCode],
                    ['name' => Str::headline($roleCode), 'description' => 'Default AutoERP semantic Finance account role.', 'is_active' => true],
                );
                $this->assign($tenantId, $organizationUnitId, $role, $account);
                FinancePostingProfileRule::query()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'posting_profile_id' => $profile->getKey(), 'line_key' => $roleCode, 'effective_from' => self::OPENING_EFFECTIVE_DATE],
                    ['account_role_id' => $role->getKey(), 'effective_to' => null, 'is_active' => true, 'description' => $definition['name'].' '.$roleCode],
                );
            }
        }
    }

    private function accountType(int $tenantId, string $code): FinanceAccountType
    {
        return FinanceAccountType::query()->where('tenant_id', $tenantId)->where('code', $code)->firstOrFail();
    }

    private function account(int $tenantId, string $code): FinanceAccount
    {
        return FinanceAccount::query()->where('tenant_id', $tenantId)->where('code', $code)->firstOrFail();
    }

    private function category(int $tenantId, FinanceAccountType $type, string $code, string $name): FinanceAccountCategory
    {
        return FinanceAccountCategory::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => $code],
            ['account_type_id' => $type->getKey(), 'name' => $name, 'description' => 'Default payment control account category.', 'is_active' => true, 'sort_order' => 100],
        );
    }

    private function postingAccount(int $tenantId, ?int $organizationUnitId, string $code, string $name, FinanceAccountType $type, FinanceAccountCategory $category, FinanceAccount $parent): FinanceAccount
    {
        return FinanceAccount::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => $code],
            [
                'organization_unit_id' => $organizationUnitId,
                'account_type_id' => $type->getKey(),
                'account_category_id' => $category->getKey(),
                'parent_id' => $parent->getKey(),
                'name' => $name,
                'normal_balance' => $type->normal_balance->value,
                'is_control_account' => true,
                'is_posting_account' => true,
                'is_cash_account' => false,
                'is_bank_account' => false,
                'is_tax_account' => false,
                'is_system' => true,
                'is_active' => true,
                'metadata' => ['seed_source' => 'finance_payment_posting'],
            ],
        );
    }

    private function assign(int $tenantId, ?int $organizationUnitId, FinanceAccountRole $role, FinanceAccount $account): void
    {
        FinanceAccountAssignment::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId, 'account_role_id' => $role->getKey(), 'effective_from' => self::OPENING_EFFECTIVE_DATE],
            ['account_id' => $account->getKey(), 'effective_to' => null, 'is_active' => true],
        );
    }
}
