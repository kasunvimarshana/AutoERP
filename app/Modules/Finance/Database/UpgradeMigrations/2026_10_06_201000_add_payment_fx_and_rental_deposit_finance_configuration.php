<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Support\TenantExecutionContext;
use Modules\Finance\Constants\FinanceSystemAccountCode;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;

return new class extends Migration
{
    private const OPENING_EFFECTIVE_DATE = '1900-01-01';

    private const TYPE_LIABILITY = 'LIABILITY';
    private const TYPE_REVENUE = 'REVENUE';
    private const TYPE_EXPENSE = 'EXPENSE';

    private const CATEGORY_CUSTOMER_DEPOSIT = 'CUSTOMER_DEPOSIT';
    private const CATEGORY_FX_GAIN = 'FX_GAIN';
    private const CATEGORY_FX_LOSS = 'FX_LOSS';

    private const PAYMENT_PROFILE_CODES = [
        FinancePostingProfileCode::CustomerReceipt->value,
        FinancePostingProfileCode::SupplierPayment->value,
        FinancePostingProfileCode::CustomerAdvance->value,
        FinancePostingProfileCode::SupplierAdvance->value,
        FinancePostingProfileCode::RentalDeposit->value,
    ];

    public function up(): void
    {
        foreach ([
            'tenants',
            'finance_account_types',
            'finance_account_categories',
            'finance_accounts',
            'finance_account_roles',
            'finance_account_assignments',
            'finance_posting_profiles',
            'finance_posting_profile_rules',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        app(TenantExecutionContext::class)->runAsControlPlane(function (): void {
            DB::transaction(function (): void {
                $tenantIds = DB::table('tenants')->orderBy('id')->pluck('id');

                foreach ($tenantIds as $tenantIdValue) {
                    $tenantId = (int) $tenantIdValue;
                    if (! $this->tenantHasFinanceConfiguration($tenantId)) {
                        continue;
                    }

                    $liabilityTypeId = $this->requiredTypeId($tenantId, self::TYPE_LIABILITY);
                    $revenueTypeId = $this->requiredTypeId($tenantId, self::TYPE_REVENUE);
                    $expenseTypeId = $this->requiredTypeId($tenantId, self::TYPE_EXPENSE);

                    $customerDepositCategoryId = $this->ensureCategory(
                        $tenantId,
                        $liabilityTypeId,
                        self::CATEGORY_CUSTOMER_DEPOSIT,
                        'Customer Security Deposits',
                    );
                    $fxGainCategoryId = $this->ensureCategory(
                        $tenantId,
                        $revenueTypeId,
                        self::CATEGORY_FX_GAIN,
                        'Realized FX Gain',
                    );
                    $fxLossCategoryId = $this->ensureCategory(
                        $tenantId,
                        $expenseTypeId,
                        self::CATEGORY_FX_LOSS,
                        'Realized FX Loss',
                    );

                    $customerDepositAccountId = $this->ensureGlobalAccount(
                        $tenantId,
                        $liabilityTypeId,
                        $customerDepositCategoryId,
                        FinanceSystemAccountCode::CUSTOMER_SECURITY_DEPOSIT,
                        'Customer Security Deposits',
                        'credit',
                        true,
                    );
                    $fxGainAccountId = $this->ensureGlobalAccount(
                        $tenantId,
                        $revenueTypeId,
                        $fxGainCategoryId,
                        FinanceSystemAccountCode::REALIZED_FX_GAIN,
                        'Realized FX Gain',
                        'credit',
                        false,
                    );
                    $fxLossAccountId = $this->ensureGlobalAccount(
                        $tenantId,
                        $expenseTypeId,
                        $fxLossCategoryId,
                        FinanceSystemAccountCode::REALIZED_FX_LOSS,
                        'Realized FX Loss',
                        'debit',
                        false,
                    );

                    $customerDepositRoleId = $this->ensureRole(
                        $tenantId,
                        FinanceAccountRoleCode::CustomerDeposit->value,
                        'Customer Deposit',
                    );
                    $fxGainRoleId = $this->ensureRole(
                        $tenantId,
                        FinanceAccountRoleCode::RealizedFxGain->value,
                        'Realized FX Gain',
                    );
                    $fxLossRoleId = $this->ensureRole(
                        $tenantId,
                        FinanceAccountRoleCode::RealizedFxLoss->value,
                        'Realized FX Loss',
                    );

                    $this->ensureGlobalAssignment($tenantId, $customerDepositRoleId, $customerDepositAccountId);
                    $this->ensureGlobalAssignment($tenantId, $fxGainRoleId, $fxGainAccountId);
                    $this->ensureGlobalAssignment($tenantId, $fxLossRoleId, $fxLossAccountId);

                    $this->ensureRentalDepositProfiles(
                        $tenantId,
                        $customerDepositRoleId,
                        $fxGainRoleId,
                        $fxLossRoleId,
                    );
                    $this->ensureFxRules($tenantId, $fxGainRoleId, $fxLossRoleId);
                }
            }, 3);
        });
    }

    public function down(): void
    {
        // Financial configuration is intentionally retained on rollback.
        // Deleting accounts, role assignments, or profile rules after journals may
        // reference them would damage auditability and historical posting semantics.
    }

    private function tenantHasFinanceConfiguration(int $tenantId): bool
    {
        return DB::table('finance_posting_profiles')
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    private function requiredTypeId(int $tenantId, string $code): int
    {
        $id = DB::table('finance_account_types')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('is_active', true)
            ->value('id');

        if ($id === null) {
            throw new RuntimeException("Finance account type [{$code}] is required for tenant [{$tenantId}].");
        }

        return (int) $id;
    }

    private function ensureCategory(int $tenantId, int $typeId, string $code, string $name): int
    {
        $existing = DB::table('finance_account_categories')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first(['id', 'account_type_id']);

        if ($existing !== null) {
            if ((int) $existing->account_type_id !== $typeId) {
                throw new RuntimeException("Finance category [{$code}] has an incompatible account type.");
            }

            return (int) $existing->id;
        }

        $sortOrder = (int) DB::table('finance_account_categories')
            ->where('tenant_id', $tenantId)
            ->max('sort_order');

        return (int) DB::table('finance_account_categories')->insertGetId([
            'tenant_id' => $tenantId,
            'account_type_id' => $typeId,
            'code' => $code,
            'name' => $name,
            'description' => 'AutoERP system Finance category.',
            'is_active' => true,
            'sort_order' => $sortOrder + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureGlobalAccount(
        int $tenantId,
        int $typeId,
        int $categoryId,
        string $code,
        string $name,
        string $normalBalance,
        bool $controlAccount,
    ): int {
        $existing = DB::table('finance_accounts')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first(['id', 'organization_unit_id', 'account_type_id', 'account_category_id']);

        if ($existing !== null) {
            if ($existing->organization_unit_id !== null
                || (int) $existing->account_type_id !== $typeId
                || (int) $existing->account_category_id !== $categoryId) {
                throw new RuntimeException("Finance system account [{$code}] exists with incompatible scope or classification.");
            }

            DB::table('finance_accounts')
                ->where('id', (int) $existing->id)
                ->update([
                    'name' => $name,
                    'normal_balance' => $normalBalance,
                    'is_control_account' => $controlAccount,
                    'is_posting_account' => true,
                    'is_system' => true,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            return (int) $existing->id;
        }

        return (int) DB::table('finance_accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'account_type_id' => $typeId,
            'account_category_id' => $categoryId,
            'parent_id' => null,
            'code' => $code,
            'name' => $name,
            'description' => 'AutoERP system Finance account.',
            'normal_balance' => $normalBalance,
            'is_control_account' => $controlAccount,
            'is_posting_account' => true,
            'is_cash_account' => false,
            'is_bank_account' => false,
            'is_tax_account' => false,
            'is_system' => true,
            'is_active' => true,
            'metadata' => json_encode(['seed_source' => 'finance_upgrade'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureRole(int $tenantId, string $code, string $name): int
    {
        $existing = DB::table('finance_account_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');

        if ($existing !== null) {
            DB::table('finance_account_roles')
                ->where('id', (int) $existing)
                ->update([
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            return (int) $existing;
        }

        return (int) DB::table('finance_account_roles')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $name,
            'description' => 'AutoERP semantic Finance account role.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureGlobalAssignment(int $tenantId, int $roleId, int $accountId): void
    {
        $existing = DB::table('finance_account_assignments')
            ->where('tenant_id', $tenantId)
            ->whereNull('organization_unit_id')
            ->where('account_role_id', $roleId)
            ->where('is_active', true)
            ->exists();

        if ($existing) {
            return;
        }

        DB::table('finance_account_assignments')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'account_role_id' => $roleId,
            'account_id' => $accountId,
            'effective_from' => self::OPENING_EFFECTIVE_DATE,
            'effective_to' => null,
            'is_active' => true,
            'created_by' => null,
            'ended_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureRentalDepositProfiles(
        int $tenantId,
        int $customerDepositRoleId,
        int $fxGainRoleId,
        int $fxLossRoleId,
    ): void {
        $scopes = DB::table('finance_posting_profiles')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('code', [
                FinancePostingProfileCode::CustomerReceipt->value,
                FinancePostingProfileCode::CustomerAdvance->value,
                FinancePostingProfileCode::RentalDeposit->value,
            ])
            ->get(['organization_unit_id'])
            ->map(static fn ($row): ?int => $row->organization_unit_id === null ? null : (int) $row->organization_unit_id)
            ->uniqueStrict()
            ->values();

        foreach ($scopes as $organizationUnitId) {
            $cashRoleId = $this->requiredRoleId($tenantId, FinanceAccountRoleCode::Cash->value);
            $bankRoleId = $this->requiredRoleId($tenantId, FinanceAccountRoleCode::Bank->value);
            $receivableRoleId = $this->requiredRoleId($tenantId, FinanceAccountRoleCode::Receivable->value);

            $profileQuery = DB::table('finance_posting_profiles')
                ->where('tenant_id', $tenantId)
                ->where('code', FinancePostingProfileCode::RentalDeposit->value);
            $organizationUnitId === null
                ? $profileQuery->whereNull('organization_unit_id')
                : $profileQuery->where('organization_unit_id', $organizationUnitId);
            $profileId = $profileQuery->value('id');

            if ($profileId === null) {
                $profileId = DB::table('finance_posting_profiles')->insertGetId([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'code' => FinancePostingProfileCode::RentalDeposit->value,
                    'name' => 'Rental Security Deposit',
                    'description' => 'AutoERP rental security deposit posting profile.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ([
                FinanceAccountRoleCode::Cash->value => $cashRoleId,
                FinanceAccountRoleCode::Bank->value => $bankRoleId,
                FinanceAccountRoleCode::Receivable->value => $receivableRoleId,
                FinanceAccountRoleCode::CustomerDeposit->value => $customerDepositRoleId,
                FinanceAccountRoleCode::RealizedFxGain->value => $fxGainRoleId,
                FinanceAccountRoleCode::RealizedFxLoss->value => $fxLossRoleId,
            ] as $lineKey => $roleId) {
                $this->ensureProfileRule($tenantId, (int) $profileId, $lineKey, $roleId);
            }
        }
    }

    private function ensureFxRules(int $tenantId, int $fxGainRoleId, int $fxLossRoleId): void
    {
        $profiles = DB::table('finance_posting_profiles')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('code', self::PAYMENT_PROFILE_CODES)
            ->get(['id']);

        foreach ($profiles as $profile) {
            $this->ensureProfileRule(
                $tenantId,
                (int) $profile->id,
                FinanceAccountRoleCode::RealizedFxGain->value,
                $fxGainRoleId,
            );
            $this->ensureProfileRule(
                $tenantId,
                (int) $profile->id,
                FinanceAccountRoleCode::RealizedFxLoss->value,
                $fxLossRoleId,
            );
        }
    }

    private function requiredRoleId(int $tenantId, string $code): int
    {
        $id = DB::table('finance_account_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('is_active', true)
            ->value('id');

        if ($id === null) {
            throw new RuntimeException("Finance account role [{$code}] is required for tenant [{$tenantId}].");
        }

        return (int) $id;
    }

    private function ensureProfileRule(int $tenantId, int $profileId, string $lineKey, int $roleId): void
    {
        if (DB::table('finance_posting_profile_rules')
            ->where('tenant_id', $tenantId)
            ->where('posting_profile_id', $profileId)
            ->where('line_key', $lineKey)
            ->exists()) {
            return;
        }

        DB::table('finance_posting_profile_rules')->insert([
            'tenant_id' => $tenantId,
            'posting_profile_id' => $profileId,
            'line_key' => $lineKey,
            'account_role_id' => $roleId,
            'effective_from' => self::OPENING_EFFECTIVE_DATE,
            'effective_to' => null,
            'is_active' => true,
            'description' => 'AutoERP system Finance mapping.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
