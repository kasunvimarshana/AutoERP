<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountAssignment;
use Modules\Finance\Models\FinanceAccountCategory;
use Modules\Finance\Models\FinanceAccountRole;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;

final class FinanceInvoicePostingSeeder extends Seeder
{
    use ResolvesSeedContext;

    private const TYPE_REVENUE = 'REVENUE';

    private const TYPE_EXPENSE = 'EXPENSE';

    private const CATEGORY_RENTAL_REVENUE = 'RENTAL_REVENUE';

    private const CATEGORY_RENTAL_EXPENSE = 'RENTAL_EXPENSE';

    private const ACCOUNT_RENTAL_REVENUE = '4300';

    private const ACCOUNT_RENTAL_EXPENSE = '5300';

    private const ROOT_REVENUE = '4000';

    private const ROOT_EXPENSE = '5000';

    private const DEFAULT_SORT_ORDER = 100;

    public function run(): void
    {
        if (! Schema::hasTable('finance_accounts')
            || ! Schema::hasTable('finance_posting_profiles')
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
            $revenueType = $this->requiredType($tenantId, self::TYPE_REVENUE);
            $expenseType = $this->requiredType($tenantId, self::TYPE_EXPENSE);
            $revenueCategory = $this->category(
                $tenantId,
                self::CATEGORY_RENTAL_REVENUE,
                'Rental Revenue',
                $revenueType,
            );
            $expenseCategory = $this->category(
                $tenantId,
                self::CATEGORY_RENTAL_EXPENSE,
                'Rental Expense',
                $expenseType,
            );
            $rentalRevenue = $this->account(
                $tenantId,
                $organizationUnitId,
                self::ACCOUNT_RENTAL_REVENUE,
                'Rental Revenue',
                $revenueType,
                $revenueCategory,
                self::ROOT_REVENUE,
            );
            $rentalExpense = $this->account(
                $tenantId,
                $organizationUnitId,
                self::ACCOUNT_RENTAL_EXPENSE,
                'Rental Expense',
                $expenseType,
                $expenseCategory,
                self::ROOT_EXPENSE,
            );

            $this->profile($tenantId, $organizationUnitId, FinancePostingProfileCode::CustomerRentalInvoice, [
                FinanceAccountRoleCode::Receivable => $this->requiredAccount($tenantId, '1100'),
                FinanceAccountRoleCode::RentalRevenue => $rentalRevenue,
                FinanceAccountRoleCode::TaxPayable => $this->requiredAccount($tenantId, '2200'),
                FinanceAccountRoleCode::WithholdingReceivable => $this->requiredAccount($tenantId, '1300'),
            ]);
            $this->profile($tenantId, $organizationUnitId, FinancePostingProfileCode::SupplierRentalInvoice, [
                FinanceAccountRoleCode::Payable => $this->requiredAccount($tenantId, '2100'),
                FinanceAccountRoleCode::RentalExpense => $rentalExpense,
                FinanceAccountRoleCode::TaxReceivable => $this->requiredAccount($tenantId, '1300'),
                FinanceAccountRoleCode::WithholdingPayable => $this->requiredAccount($tenantId, '2200'),
            ]);
        }, 3);
    }

    private function requiredType(int $tenantId, string $code): FinanceAccountType
    {
        $type = FinanceAccountType::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        if (! $type instanceof FinanceAccountType) {
            throw new InvalidArgumentException("Finance account type [{$code}] must be seeded before invoice posting defaults.");
        }

        return $type;
    }

    private function category(
        int $tenantId,
        string $code,
        string $name,
        FinanceAccountType $type,
    ): FinanceAccountCategory {
        $category = FinanceAccountCategory::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        if ($category instanceof FinanceAccountCategory) {
            if ((int) $category->account_type_id !== (int) $type->getKey()) {
                throw new InvalidArgumentException("Finance category code [{$code}] conflicts with the rental posting catalogue.");
            }

            return $category;
        }

        return FinanceAccountCategory::query()->create([
            'tenant_id' => $tenantId,
            'account_type_id' => $type->getKey(),
            'code' => $code,
            'name' => $name,
            'description' => 'Default AutoERP rental posting category.',
            'is_active' => true,
            'sort_order' => self::DEFAULT_SORT_ORDER,
        ]);
    }

    private function account(
        int $tenantId,
        ?int $organizationUnitId,
        string $code,
        string $name,
        FinanceAccountType $type,
        FinanceAccountCategory $category,
        string $parentCode,
    ): FinanceAccount {
        $account = FinanceAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        if ($account instanceof FinanceAccount) {
            if ($account->organization_unit_id !== $organizationUnitId
                || (int) $account->account_type_id !== (int) $type->getKey()
                || (int) $account->account_category_id !== (int) $category->getKey()
                || ! (bool) $account->is_posting_account) {
                throw new InvalidArgumentException("Finance account code [{$code}] conflicts with the rental posting catalogue.");
            }

            return $account;
        }

        $parent = $this->requiredAccount($tenantId, $parentCode);

        return FinanceAccount::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_type_id' => $type->getKey(),
            'account_category_id' => $category->getKey(),
            'parent_id' => $parent->getKey(),
            'code' => $code,
            'name' => $name,
            'normal_balance' => $type->normal_balance->value,
            'is_control_account' => false,
            'is_posting_account' => true,
            'is_system' => true,
            'is_active' => true,
            'metadata' => ['seed_source' => 'finance_invoice_posting'],
        ]);
    }

    private function requiredAccount(int $tenantId, string $code): FinanceAccount
    {
        $account = FinanceAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        if (! $account instanceof FinanceAccount) {
            throw new InvalidArgumentException("Finance account [{$code}] must be seeded before invoice posting defaults.");
        }

        return $account;
    }

    /** @param array<FinanceAccountRoleCode, FinanceAccount> $rules */
    private function profile(
        int $tenantId,
        ?int $organizationUnitId,
        FinancePostingProfileCode $code,
        array $rules,
    ): void {
        $profile = FinancePostingProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('code', $code->value)
            ->first();
        if (! $profile instanceof FinancePostingProfile) {
            $profile = FinancePostingProfile::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'code' => $code->value,
                'name' => match ($code) {
                    FinancePostingProfileCode::CustomerRentalInvoice => 'Customer Rental Invoice',
                    FinancePostingProfileCode::SupplierRentalInvoice => 'Supplier Rental Invoice',
                    default => throw new InvalidArgumentException('Unsupported rental invoice posting profile.'),
                },
                'description' => 'Default AutoERP rental invoice posting profile.',
                'is_active' => true,
            ]);
        }

        foreach ($rules as $roleCode => $account) {
            if (! $roleCode instanceof FinanceAccountRoleCode || ! $account instanceof FinanceAccount) {
                throw new InvalidArgumentException('Rental invoice posting rules are invalid.');
            }
            $role = FinanceAccountRole::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => $roleCode->value],
                [
                    'name' => str_replace('_', ' ', ucfirst($roleCode->value)),
                    'description' => 'Default AutoERP semantic Finance account role.',
                    'is_active' => true,
                ],
            );
            $this->assignment($tenantId, $organizationUnitId, $role, $account);
            FinancePostingProfileRule::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'posting_profile_id' => $profile->getKey(),
                    'line_key' => $roleCode->value,
                    'effective_from' => FinancePostingProfileRule::OPENING_EFFECTIVE_DATE,
                ],
                [
                    'account_role_id' => $role->getKey(),
                    'effective_to' => null,
                    'is_active' => true,
                    'description' => $profile->name.' '.$roleCode->value,
                ],
            );
        }
    }

    private function assignment(
        int $tenantId,
        ?int $organizationUnitId,
        FinanceAccountRole $role,
        FinanceAccount $defaultAccount,
    ): void {
        $existing = FinanceAccountAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('account_role_id', $role->getKey())
            ->whereDate('effective_from', FinancePostingProfileRule::OPENING_EFFECTIVE_DATE)
            ->whereNull('effective_to')
            ->where('is_active', true)
            ->first();
        if ($existing instanceof FinanceAccountAssignment) {
            return;
        }

        FinanceAccountAssignment::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_role_id' => $role->getKey(),
            'account_id' => $defaultAccount->getKey(),
            'effective_from' => FinancePostingProfileRule::OPENING_EFFECTIVE_DATE,
            'effective_to' => null,
            'is_active' => true,
        ]);
    }
}
