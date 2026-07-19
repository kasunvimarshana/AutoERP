<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;

final class FinancePostingFixture
{
    private const OPENING_EFFECTIVE_DATE = '1900-01-01';

    private const ASSET_TYPE = 'ASSET';
    private const LIABILITY_TYPE = 'LIABILITY';
    private const REVENUE_TYPE = 'REVENUE';
    private const EXPENSE_TYPE = 'EXPENSE';

    private const CASH_ACCOUNT = '1010';
    private const BANK_ACCOUNT = '1020';
    private const RECEIVABLE_ACCOUNT = '1100';
    private const INVENTORY_ACCOUNT = '1200';
    private const TAX_RECEIVABLE_ACCOUNT = '1300';
    private const SUPPLIER_ADVANCE_ACCOUNT = '1400';
    private const PAYABLE_ACCOUNT = '2100';
    private const GRNI_ACCOUNT = '2150';
    private const TAX_PAYABLE_ACCOUNT = '2200';
    private const CUSTOMER_ADVANCE_ACCOUNT = '2300';
    private const CUSTOMER_DEPOSIT_ACCOUNT = '2310';
    private const SALES_REVENUE_ACCOUNT = '4100';
    private const SERVICE_REVENUE_ACCOUNT = '4200';
    private const RENTAL_REVENUE_ACCOUNT = '4300';
    private const PURCHASE_EXPENSE_ACCOUNT = '5100';
    private const COST_OF_GOODS_SOLD_ACCOUNT = '5200';
    private const RENTAL_EXPENSE_ACCOUNT = '5300';

    public static function seedCustomerPaymentProfiles(int $tenantId, ?int $organizationUnitId = null): void
    {
        self::seedCustomerInvoiceProfiles($tenantId, $organizationUnitId);

        $accounts = self::accounts($tenantId, $organizationUnitId);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::CustomerReceipt->value, [
            FinanceAccountRoleCode::Cash->value => $accounts[FinanceAccountRoleCode::Cash->value],
            FinanceAccountRoleCode::Bank->value => $accounts[FinanceAccountRoleCode::Bank->value],
            FinanceAccountRoleCode::Receivable->value => $accounts[FinanceAccountRoleCode::Receivable->value],
            FinanceAccountRoleCode::CustomerAdvance->value => $accounts[FinanceAccountRoleCode::CustomerAdvance->value],
        ]);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::CustomerAdvance->value, [
            FinanceAccountRoleCode::Cash->value => $accounts[FinanceAccountRoleCode::Cash->value],
            FinanceAccountRoleCode::Bank->value => $accounts[FinanceAccountRoleCode::Bank->value],
            FinanceAccountRoleCode::Receivable->value => $accounts[FinanceAccountRoleCode::Receivable->value],
            FinanceAccountRoleCode::CustomerAdvance->value => $accounts[FinanceAccountRoleCode::CustomerAdvance->value],
        ]);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::RentalDeposit->value, [
            FinanceAccountRoleCode::Cash->value => $accounts[FinanceAccountRoleCode::Cash->value],
            FinanceAccountRoleCode::Bank->value => $accounts[FinanceAccountRoleCode::Bank->value],
            FinanceAccountRoleCode::Receivable->value => $accounts[FinanceAccountRoleCode::Receivable->value],
            FinanceAccountRoleCode::CustomerDeposit->value => $accounts[FinanceAccountRoleCode::CustomerDeposit->value],
        ]);
    }

    public static function seedSupplierPaymentProfiles(int $tenantId, ?int $organizationUnitId = null): void
    {
        self::seedSupplierInvoiceProfiles($tenantId, $organizationUnitId);

        $accounts = self::accounts($tenantId, $organizationUnitId);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::SupplierPayment->value, [
            FinanceAccountRoleCode::Cash->value => $accounts[FinanceAccountRoleCode::Cash->value],
            FinanceAccountRoleCode::Bank->value => $accounts[FinanceAccountRoleCode::Bank->value],
            FinanceAccountRoleCode::Payable->value => $accounts[FinanceAccountRoleCode::Payable->value],
            FinanceAccountRoleCode::SupplierAdvance->value => $accounts[FinanceAccountRoleCode::SupplierAdvance->value],
        ]);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::SupplierAdvance->value, [
            FinanceAccountRoleCode::Cash->value => $accounts[FinanceAccountRoleCode::Cash->value],
            FinanceAccountRoleCode::Bank->value => $accounts[FinanceAccountRoleCode::Bank->value],
            FinanceAccountRoleCode::Payable->value => $accounts[FinanceAccountRoleCode::Payable->value],
            FinanceAccountRoleCode::SupplierAdvance->value => $accounts[FinanceAccountRoleCode::SupplierAdvance->value],
        ]);
    }

    public static function seedPurchasePostingProfiles(int $tenantId, ?int $organizationUnitId = null): void
    {
        $accounts = self::accounts($tenantId, $organizationUnitId);
        self::seedInventoryReceiptProfile($tenantId, $organizationUnitId, $accounts);
        self::seedPurchaseInvoiceProfile($tenantId, $organizationUnitId, $accounts);
    }

    public static function seedCustomerInvoiceProfiles(int $tenantId, ?int $organizationUnitId = null): void
    {
        $accounts = self::accounts($tenantId, $organizationUnitId);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::SalesInvoice->value, [
            FinanceAccountRoleCode::Receivable->value => $accounts[FinanceAccountRoleCode::Receivable->value],
            FinanceAccountRoleCode::Revenue->value => $accounts[FinanceAccountRoleCode::Revenue->value],
            FinanceAccountRoleCode::TaxPayable->value => $accounts[FinanceAccountRoleCode::TaxPayable->value],
            FinanceAccountRoleCode::WithholdingReceivable->value => $accounts[FinanceAccountRoleCode::WithholdingReceivable->value],
        ]);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::VehicleServiceInvoice->value, [
            FinanceAccountRoleCode::Receivable->value => $accounts[FinanceAccountRoleCode::Receivable->value],
            FinanceAccountRoleCode::ServiceRevenue->value => $accounts[FinanceAccountRoleCode::ServiceRevenue->value],
            FinanceAccountRoleCode::TaxPayable->value => $accounts[FinanceAccountRoleCode::TaxPayable->value],
        ]);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::CustomerRentalInvoice->value, [
            FinanceAccountRoleCode::Receivable->value => $accounts[FinanceAccountRoleCode::Receivable->value],
            FinanceAccountRoleCode::RentalRevenue->value => $accounts[FinanceAccountRoleCode::RentalRevenue->value],
            FinanceAccountRoleCode::TaxPayable->value => $accounts[FinanceAccountRoleCode::TaxPayable->value],
            FinanceAccountRoleCode::WithholdingReceivable->value => $accounts[FinanceAccountRoleCode::WithholdingReceivable->value],
        ]);
        self::seedInventoryIssueProfile($tenantId, $organizationUnitId, $accounts);
    }

    public static function seedSupplierInvoiceProfiles(int $tenantId, ?int $organizationUnitId = null): void
    {
        $accounts = self::accounts($tenantId, $organizationUnitId);
        self::seedInventoryReceiptProfile($tenantId, $organizationUnitId, $accounts);
        self::seedPurchaseInvoiceProfile($tenantId, $organizationUnitId, $accounts);
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::SupplierRentalInvoice->value, [
            FinanceAccountRoleCode::Payable->value => $accounts[FinanceAccountRoleCode::Payable->value],
            FinanceAccountRoleCode::RentalExpense->value => $accounts[FinanceAccountRoleCode::RentalExpense->value],
            FinanceAccountRoleCode::TaxReceivable->value => $accounts[FinanceAccountRoleCode::TaxReceivable->value],
            FinanceAccountRoleCode::WithholdingPayable->value => $accounts[FinanceAccountRoleCode::WithholdingPayable->value],
        ]);
    }

    public static function seedPurchaseWithholdingRole(int $tenantId, ?int $organizationUnitId = null): void
    {
        $accounts = self::accounts($tenantId, $organizationUnitId);
        self::seedPurchaseInvoiceProfile($tenantId, $organizationUnitId, $accounts);
    }

    /** @param array<string, int> $accounts */
    private static function seedInventoryReceiptProfile(
        int $tenantId,
        ?int $organizationUnitId,
        array $accounts,
    ): void {
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::InventoryReceipt->value, [
            FinanceAccountRoleCode::Inventory->value => $accounts[FinanceAccountRoleCode::Inventory->value],
            FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value => $accounts[FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value],
        ]);
    }

    /** @param array<string, int> $accounts */
    private static function seedInventoryIssueProfile(
        int $tenantId,
        ?int $organizationUnitId,
        array $accounts,
    ): void {
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::InventoryIssue->value, [
            FinanceAccountRoleCode::CostOfGoodsSold->value => $accounts[FinanceAccountRoleCode::CostOfGoodsSold->value],
            FinanceAccountRoleCode::Inventory->value => $accounts[FinanceAccountRoleCode::Inventory->value],
        ]);
    }

    /** @param array<string, int> $accounts */
    private static function seedPurchaseInvoiceProfile(
        int $tenantId,
        ?int $organizationUnitId,
        array $accounts,
    ): void {
        self::profile($tenantId, $organizationUnitId, FinancePostingProfileCode::PurchaseInvoice->value, [
            FinanceAccountRoleCode::Expense->value => $accounts[FinanceAccountRoleCode::Expense->value],
            FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value => $accounts[FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value],
            FinanceAccountRoleCode::Payable->value => $accounts[FinanceAccountRoleCode::Payable->value],
            FinanceAccountRoleCode::TaxReceivable->value => $accounts[FinanceAccountRoleCode::TaxReceivable->value],
            FinanceAccountRoleCode::WithholdingPayable->value => $accounts[FinanceAccountRoleCode::WithholdingPayable->value],
        ]);
    }

    /** @return array<string, int> */
    private static function accounts(int $tenantId, ?int $organizationUnitId): array
    {
        $assetTypeId = self::accountType($tenantId, self::ASSET_TYPE, 'debit', 'balance_sheet');
        $liabilityTypeId = self::accountType($tenantId, self::LIABILITY_TYPE, 'credit', 'balance_sheet');
        $revenueTypeId = self::accountType($tenantId, self::REVENUE_TYPE, 'credit', 'income_statement');
        $expenseTypeId = self::accountType($tenantId, self::EXPENSE_TYPE, 'debit', 'income_statement');

        return [
            FinanceAccountRoleCode::Cash->value => self::account($tenantId, $organizationUnitId, $assetTypeId, self::CASH_ACCOUNT, 'Cash', 'debit', isCash: true),
            FinanceAccountRoleCode::Bank->value => self::account($tenantId, $organizationUnitId, $assetTypeId, self::BANK_ACCOUNT, 'Bank', 'debit', isBank: true),
            FinanceAccountRoleCode::Receivable->value => self::account($tenantId, $organizationUnitId, $assetTypeId, self::RECEIVABLE_ACCOUNT, 'Accounts Receivable', 'debit'),
            FinanceAccountRoleCode::Inventory->value => self::account($tenantId, $organizationUnitId, $assetTypeId, self::INVENTORY_ACCOUNT, 'Inventory', 'debit'),
            FinanceAccountRoleCode::TaxReceivable->value => self::account($tenantId, $organizationUnitId, $assetTypeId, self::TAX_RECEIVABLE_ACCOUNT, 'Tax Receivable', 'debit', isTax: true),
            FinanceAccountRoleCode::WithholdingReceivable->value => self::account($tenantId, $organizationUnitId, $assetTypeId, self::TAX_RECEIVABLE_ACCOUNT, 'Tax Receivable', 'debit', isTax: true),
            FinanceAccountRoleCode::SupplierAdvance->value => self::account($tenantId, $organizationUnitId, $assetTypeId, self::SUPPLIER_ADVANCE_ACCOUNT, 'Supplier Advances', 'debit'),
            FinanceAccountRoleCode::Payable->value => self::account($tenantId, $organizationUnitId, $liabilityTypeId, self::PAYABLE_ACCOUNT, 'Accounts Payable', 'credit'),
            FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value => self::account($tenantId, $organizationUnitId, $liabilityTypeId, self::GRNI_ACCOUNT, 'Goods Received Not Invoiced', 'credit'),
            FinanceAccountRoleCode::TaxPayable->value => self::account($tenantId, $organizationUnitId, $liabilityTypeId, self::TAX_PAYABLE_ACCOUNT, 'Tax Payable', 'credit', isTax: true),
            FinanceAccountRoleCode::WithholdingPayable->value => self::account($tenantId, $organizationUnitId, $liabilityTypeId, self::TAX_PAYABLE_ACCOUNT, 'Tax Payable', 'credit', isTax: true),
            FinanceAccountRoleCode::CustomerAdvance->value => self::account($tenantId, $organizationUnitId, $liabilityTypeId, self::CUSTOMER_ADVANCE_ACCOUNT, 'Customer Advances', 'credit'),
            FinanceAccountRoleCode::CustomerDeposit->value => self::account($tenantId, $organizationUnitId, $liabilityTypeId, self::CUSTOMER_DEPOSIT_ACCOUNT, 'Rental Security Deposits', 'credit'),
            FinanceAccountRoleCode::Revenue->value => self::account($tenantId, $organizationUnitId, $revenueTypeId, self::SALES_REVENUE_ACCOUNT, 'Sales Revenue', 'credit'),
            FinanceAccountRoleCode::ServiceRevenue->value => self::account($tenantId, $organizationUnitId, $revenueTypeId, self::SERVICE_REVENUE_ACCOUNT, 'Service Revenue', 'credit'),
            FinanceAccountRoleCode::RentalRevenue->value => self::account($tenantId, $organizationUnitId, $revenueTypeId, self::RENTAL_REVENUE_ACCOUNT, 'Rental Revenue', 'credit'),
            FinanceAccountRoleCode::Expense->value => self::account($tenantId, $organizationUnitId, $expenseTypeId, self::PURCHASE_EXPENSE_ACCOUNT, 'Purchase Expense', 'debit'),
            FinanceAccountRoleCode::CostOfGoodsSold->value => self::account($tenantId, $organizationUnitId, $expenseTypeId, self::COST_OF_GOODS_SOLD_ACCOUNT, 'Cost of Goods Sold', 'debit'),
            FinanceAccountRoleCode::RentalExpense->value => self::account($tenantId, $organizationUnitId, $expenseTypeId, self::RENTAL_EXPENSE_ACCOUNT, 'Rental Expense', 'debit'),
        ];
    }

    private static function accountType(
        int $tenantId,
        string $code,
        string $normalBalance,
        string $statementType,
    ): int {
        $id = DB::table('finance_account_types')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
        $values = [
            'name' => Str::headline($code),
            'normal_balance' => $normalBalance,
            'statement_type' => $statementType,
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
        $scope = DB::table('finance_account_assignments')
            ->where('tenant_id', $tenantId)
            ->where('account_role_id', $roleId);
        $organizationUnitId === null
            ? $scope->whereNull('organization_unit_id')
            : $scope->where('organization_unit_id', $organizationUnitId);

        $opening = (clone $scope)
            ->whereDate('effective_from', self::OPENING_EFFECTIVE_DATE)
            ->orderBy('id')
            ->first();
        $values = [
            'account_id' => $accountId,
            'effective_to' => null,
            'is_active' => true,
            'updated_at' => now(),
        ];

        if ($opening !== null) {
            DB::table('finance_account_assignments')
                ->where('id', (int) $opening->id)
                ->update($values);
            (clone $scope)
                ->where('id', '!=', (int) $opening->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            return;
        }

        (clone $scope)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

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
