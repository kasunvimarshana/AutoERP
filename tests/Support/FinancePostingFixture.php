<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Enums\PaymentPostingProfile;
use Modules\Payment\Enums\PaymentPostingRole;

final class FinancePostingFixture
{
    private const OPENING_EFFECTIVE_DATE = '1900-01-01';

    private const ASSET_TYPE = 'ASSET';
    private const LIABILITY_TYPE = 'LIABILITY';

    private const CASH_ACCOUNT = '1010';
    private const BANK_ACCOUNT = '1020';
    private const RECEIVABLE_ACCOUNT = '1100';
    private const SUPPLIER_ADVANCE_ACCOUNT = '1400';
    private const PAYABLE_ACCOUNT = '2100';
    private const WITHHOLDING_PAYABLE_ACCOUNT = '2200';
    private const CUSTOMER_ADVANCE_ACCOUNT = '2300';
    private const CUSTOMER_DEPOSIT_ACCOUNT = '2310';

    public static function seedCustomerPaymentProfiles(int $tenantId, ?int $organizationUnitId = null): void
    {
        $accounts = self::baseAccounts($tenantId, $organizationUnitId);
        self::profile($tenantId, $organizationUnitId, PaymentPostingProfile::CustomerSettlement->value, [
            PaymentPostingRole::Cash->value => $accounts[PaymentPostingRole::Cash->value],
            PaymentPostingRole::Bank->value => $accounts[PaymentPostingRole::Bank->value],
            PaymentPostingRole::Receivable->value => $accounts[PaymentPostingRole::Receivable->value],
            PaymentPostingRole::CustomerAdvance->value => $accounts[PaymentPostingRole::CustomerAdvance->value],
        ]);
        self::profile($tenantId, $organizationUnitId, PaymentPostingProfile::CustomerAdvance->value, [
            PaymentPostingRole::Cash->value => $accounts[PaymentPostingRole::Cash->value],
            PaymentPostingRole::Bank->value => $accounts[PaymentPostingRole::Bank->value],
            PaymentPostingRole::Receivable->value => $accounts[PaymentPostingRole::Receivable->value],
            PaymentPostingRole::CustomerAdvance->value => $accounts[PaymentPostingRole::CustomerAdvance->value],
        ]);
        self::profile($tenantId, $organizationUnitId, PaymentPostingProfile::RentalDeposit->value, [
            PaymentPostingRole::Cash->value => $accounts[PaymentPostingRole::Cash->value],
            PaymentPostingRole::Bank->value => $accounts[PaymentPostingRole::Bank->value],
            PaymentPostingRole::Receivable->value => $accounts[PaymentPostingRole::Receivable->value],
            PaymentPostingRole::CustomerDeposit->value => $accounts[PaymentPostingRole::CustomerDeposit->value],
        ]);
    }

    public static function seedSupplierPaymentProfiles(int $tenantId, ?int $organizationUnitId = null): void
    {
        $accounts = self::baseAccounts($tenantId, $organizationUnitId);
        self::profile($tenantId, $organizationUnitId, PaymentPostingProfile::SupplierSettlement->value, [
            PaymentPostingRole::Cash->value => $accounts[PaymentPostingRole::Cash->value],
            PaymentPostingRole::Bank->value => $accounts[PaymentPostingRole::Bank->value],
            PaymentPostingRole::Payable->value => $accounts[PaymentPostingRole::Payable->value],
            PaymentPostingRole::SupplierAdvance->value => $accounts[PaymentPostingRole::SupplierAdvance->value],
        ]);
        self::profile($tenantId, $organizationUnitId, PaymentPostingProfile::SupplierAdvance->value, [
            PaymentPostingRole::Cash->value => $accounts[PaymentPostingRole::Cash->value],
            PaymentPostingRole::Bank->value => $accounts[PaymentPostingRole::Bank->value],
            PaymentPostingRole::Payable->value => $accounts[PaymentPostingRole::Payable->value],
            PaymentPostingRole::SupplierAdvance->value => $accounts[PaymentPostingRole::SupplierAdvance->value],
        ]);
    }

    public static function seedPurchaseWithholdingRole(int $tenantId, ?int $organizationUnitId = null): void
    {
        $accounts = self::baseAccounts($tenantId, $organizationUnitId);
        $profileId = self::profileId($tenantId, $organizationUnitId, 'purchase_invoice', 'Purchase Invoice');
        self::rule(
            $tenantId,
            $organizationUnitId,
            $profileId,
            'withholding_payable',
            $accounts['withholding_payable'],
        );
    }

    /** @return array<string, int> */
    private static function baseAccounts(int $tenantId, ?int $organizationUnitId): array
    {
        $assetTypeId = self::accountType($tenantId, self::ASSET_TYPE, 'debit');
        $liabilityTypeId = self::accountType($tenantId, self::LIABILITY_TYPE, 'credit');

        return [
            PaymentPostingRole::Cash->value => self::account(
                $tenantId,
                $organizationUnitId,
                $assetTypeId,
                self::CASH_ACCOUNT,
                'Cash',
                'debit',
                isCash: true,
            ),
            PaymentPostingRole::Bank->value => self::account(
                $tenantId,
                $organizationUnitId,
                $assetTypeId,
                self::BANK_ACCOUNT,
                'Bank',
                'debit',
                isBank: true,
            ),
            PaymentPostingRole::Receivable->value => self::account(
                $tenantId,
                $organizationUnitId,
                $assetTypeId,
                self::RECEIVABLE_ACCOUNT,
                'Accounts Receivable',
                'debit',
            ),
            PaymentPostingRole::SupplierAdvance->value => self::account(
                $tenantId,
                $organizationUnitId,
                $assetTypeId,
                self::SUPPLIER_ADVANCE_ACCOUNT,
                'Supplier Advances',
                'debit',
            ),
            PaymentPostingRole::Payable->value => self::account(
                $tenantId,
                $organizationUnitId,
                $liabilityTypeId,
                self::PAYABLE_ACCOUNT,
                'Accounts Payable',
                'credit',
            ),
            'withholding_payable' => self::account(
                $tenantId,
                $organizationUnitId,
                $liabilityTypeId,
                self::WITHHOLDING_PAYABLE_ACCOUNT,
                'Withholding Payable',
                'credit',
                isTax: true,
            ),
            PaymentPostingRole::CustomerAdvance->value => self::account(
                $tenantId,
                $organizationUnitId,
                $liabilityTypeId,
                self::CUSTOMER_ADVANCE_ACCOUNT,
                'Customer Advances',
                'credit',
            ),
            PaymentPostingRole::CustomerDeposit->value => self::account(
                $tenantId,
                $organizationUnitId,
                $liabilityTypeId,
                self::CUSTOMER_DEPOSIT_ACCOUNT,
                'Rental Security Deposits',
                'credit',
            ),
        ];
    }

    private static function accountType(int $tenantId, string $code, string $normalBalance): int
    {
        $id = DB::table('finance_account_types')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
        $values = [
            'name' => Str::headline($code),
            'normal_balance' => $normalBalance,
            'statement_type' => 'balance_sheet',
            'is_active' => true,
            'updated_at' => now(),
        ];
        if ($id !== null) {
            DB::table('finance_account_types')->where('id', $id)->update($values);

            return (int) $id;
        }

        return (int) DB::table('finance_account_types')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            ...$values,
            'created_at' => now(),
        ]);
    }

    private static function account(
        int $tenantId,
        ?int $organizationUnitId,
        int $accountTypeId,
        string $code,
        string $name,
        string $normalBalance,
        bool $isCash = false,
        bool $isBank = false,
        bool $isTax = false,
    ): int {
        $id = DB::table('finance_accounts')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
        $values = [
            'organization_unit_id' => $organizationUnitId,
            'account_type_id' => $accountTypeId,
            'name' => $name,
            'normal_balance' => $normalBalance,
            'is_posting_account' => true,
            'is_cash_account' => $isCash,
            'is_bank_account' => $isBank,
            'is_tax_account' => $isTax,
            'is_active' => true,
            'updated_at' => now(),
        ];
        if ($id !== null) {
            DB::table('finance_accounts')->where('id', $id)->update($values);

            return (int) $id;
        }

        return (int) DB::table('finance_accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            ...$values,
            'created_at' => now(),
        ]);
    }

    /** @param array<string, int> $rules */
    private static function profile(
        int $tenantId,
        ?int $organizationUnitId,
        string $profileCode,
        array $rules,
    ): void {
        $profileId = self::profileId($tenantId, $organizationUnitId, $profileCode, Str::headline($profileCode));
        foreach ($rules as $lineKey => $accountId) {
            self::rule($tenantId, $organizationUnitId, $profileId, $lineKey, $accountId);
        }
    }

    private static function profileId(
        int $tenantId,
        ?int $organizationUnitId,
        string $profileCode,
        string $name,
    ): int {
        $query = DB::table('finance_posting_profiles')
            ->where('tenant_id', $tenantId)
            ->where('code', $profileCode);
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
        $id = $query->value('id');
        $values = [
            'organization_unit_id' => $organizationUnitId,
            'name' => $name,
            'is_active' => true,
            'updated_at' => now(),
        ];
        if ($id !== null) {
            DB::table('finance_posting_profiles')->where('id', $id)->update($values);

            return (int) $id;
        }

        return (int) DB::table('finance_posting_profiles')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $profileCode,
            ...$values,
            'created_at' => now(),
        ]);
    }

    private static function rule(
        int $tenantId,
        ?int $organizationUnitId,
        int $profileId,
        string $lineKey,
        int $accountId,
    ): void {
        $roleId = self::role($tenantId, $lineKey);
        self::assignment($tenantId, $organizationUnitId, $roleId, $accountId);

        $query = DB::table('finance_posting_profile_rules')
            ->where('tenant_id', $tenantId)
            ->where('posting_profile_id', $profileId)
            ->where('line_key', $lineKey)
            ->whereDate('effective_from', self::OPENING_EFFECTIVE_DATE);
        $values = [
            'account_role_id' => $roleId,
            'effective_to' => null,
            'is_active' => true,
            'updated_at' => now(),
        ];
        if ($query->exists()) {
            $query->update($values);

            return;
        }
        DB::table('finance_posting_profile_rules')->insert([
            'tenant_id' => $tenantId,
            'posting_profile_id' => $profileId,
            'line_key' => $lineKey,
            'effective_from' => self::OPENING_EFFECTIVE_DATE,
            ...$values,
            'created_at' => now(),
        ]);
    }

    private static function role(int $tenantId, string $code): int
    {
        $id = DB::table('finance_account_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
        $values = [
            'name' => Str::headline($code),
            'is_active' => true,
            'updated_at' => now(),
        ];
        if ($id !== null) {
            DB::table('finance_account_roles')->where('id', $id)->update($values);

            return (int) $id;
        }

        return (int) DB::table('finance_account_roles')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            ...$values,
            'created_at' => now(),
        ]);
    }

    private static function assignment(
        int $tenantId,
        ?int $organizationUnitId,
        int $roleId,
        int $accountId,
    ): void {
        $query = DB::table('finance_account_assignments')
            ->where('tenant_id', $tenantId)
            ->where('account_role_id', $roleId)
            ->whereDate('effective_from', self::OPENING_EFFECTIVE_DATE);
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
        $values = [
            'account_id' => $accountId,
            'effective_to' => null,
            'is_active' => true,
            'updated_at' => now(),
        ];
        if ($query->exists()) {
            $query->update($values);

            return;
        }
        DB::table('finance_account_assignments')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'account_role_id' => $roleId,
            'effective_from' => self::OPENING_EFFECTIVE_DATE,
            ...$values,
            'created_at' => now(),
        ]);
    }
}
