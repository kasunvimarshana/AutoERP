<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    /** @var list<string> */
    private const RENTAL_TABLES = [
        'rental_calculation_sources',
        'rental_calculation_lines',
        'rental_calculation_runs',
        'rental_billing_periods',
        'rental_deposit_links',
        'rental_deposit_requirements',
        'rental_expense_allocations',
        'rental_expenses',
        'rental_usage_facts',
        'rental_usage_events',
        'rental_usage_contexts',
        'rental_usage_logs',
        'rental_custody_event_items',
        'rental_custody_events',
        'rental_driver_assignments',
        'rental_vehicle_replacements',
        'rental_status_histories',
        'vehicle_finance_status_histories',
        'vehicle_finance_installments',
        'rental_vehicle_allocations',
        'rental_agreement_rate_components',
        'rental_agreement_rate_versions',
        'rental_agreement_terms',
        'vehicle_finance_agreements',
        'rental_agreements',
        'rental_reservations',
    ];

    /** @var list<string> */
    private const INVOICE_TYPES = ['rental', 'vehicle_finance'];

    /** @var list<string> */
    private const SOURCE_TYPES = [
        'rental_calculation_run',
        'rental_calculation_line',
        'vehicle_finance_installment',
        'vehicle_finance_installment_component',
        'rental_deposit_requirement',
    ];

    /** @var list<string> */
    private const FINANCE_PROFILE_CODES = [
        'customer_rental_invoice',
        'supplier_rental_invoice',
        'rental_deposit',
    ];

    /** @var list<string> */
    private const FINANCE_ROLE_CODES = [
        'rental_revenue',
        'rental_expense',
        'customer_deposit',
    ];

    /** @var list<string> */
    private const FINANCE_ACCOUNT_CODES = ['2310', '4300', '5300'];

    /** @var list<string> */
    private const FINANCE_CATEGORY_CODES = [
        'CUSTOMER_DEPOSIT',
        'RENTAL_REVENUE',
        'RENTAL_EXPENSE',
    ];

    public function up(): void
    {
        $this->assertModuleDataIsEmpty();
        $this->assertExternalFinancialDataIsEmpty();
        $this->removePermissionCatalogue();
        $this->removeFinanceConfiguration();

        foreach (self::RENTAL_TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Vehicle Rental removal is irreversible. Restore the pre-removal release and database backup instead of recreating the retired schema.',
        );
    }

    private function assertModuleDataIsEmpty(): void
    {
        foreach (self::RENTAL_TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException(
                    "Vehicle Rental cannot be removed while [{$table}] contains records. Archive or migrate the module data before deploying this release.",
                );
            }
        }
    }

    private function assertExternalFinancialDataIsEmpty(): void
    {
        $this->assertNoValues('invoices', 'invoice_type', self::INVOICE_TYPES);
        $this->assertNoValues('invoice_sources', 'source_type', self::SOURCE_TYPES);
        $this->assertNoValues('invoice_source_lines', 'source_type', self::SOURCE_TYPES);
        $this->assertNoValues('invoice_source_lines', 'source_line_type', self::SOURCE_TYPES);
        $this->assertNoValues('invoice_adjustments', 'source_type', self::SOURCE_TYPES);
        $this->assertNoValues('payments', 'payment_type', ['rental_receipt']);
        $this->assertNoValues('payments', 'source_type', self::SOURCE_TYPES);
        $this->assertNoValues('tax_transactions', 'source_type', self::SOURCE_TYPES);
        $this->assertNoValues('finance_journal_entries', 'source_type', self::SOURCE_TYPES);

        if (Schema::hasTable('tax_transactions')
            && Schema::hasColumn('tax_transactions', 'source_module')
            && DB::table('tax_transactions')->where('source_module', 'vehicle_rental')->exists()) {
            $this->blocked('tax_transactions.source_module');
        }

        if (Schema::hasTable('finance_journal_entries')
            && Schema::hasColumn('finance_journal_entries', 'source_module')
            && DB::table('finance_journal_entries')->where('source_module', 'vehicle_rental')->exists()) {
            $this->blocked('finance_journal_entries.source_module');
        }
    }

    private function removePermissionCatalogue(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('module', 'vehicle-rental')
            ->pluck('id')
            ->all();

        if ($permissionIds !== [] && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        if ($permissionIds !== []) {
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }

    private function removeFinanceConfiguration(): void
    {
        if (! Schema::hasTable('finance_posting_profiles')) {
            return;
        }

        $profileIds = DB::table('finance_posting_profiles')
            ->whereIn('code', self::FINANCE_PROFILE_CODES)
            ->pluck('id')
            ->all();
        $roleIds = Schema::hasTable('finance_account_roles')
            ? DB::table('finance_account_roles')->whereIn('code', self::FINANCE_ROLE_CODES)->pluck('id')->all()
            : [];
        $accountIds = Schema::hasTable('finance_accounts')
            ? DB::table('finance_accounts')->whereIn('code', self::FINANCE_ACCOUNT_CODES)->pluck('id')->all()
            : [];

        if ($profileIds !== []
            && Schema::hasTable('finance_journal_entries')
            && DB::table('finance_journal_entries')->whereIn('posting_profile_id', $profileIds)->exists()) {
            $this->blocked('finance_journal_entries.posting_profile_id');
        }

        if ($roleIds !== [] && Schema::hasTable('finance_posting_profile_rules')) {
            $customRule = DB::table('finance_posting_profile_rules')
                ->whereIn('account_role_id', $roleIds)
                ->when($profileIds !== [], fn ($query) => $query->whereNotIn('posting_profile_id', $profileIds))
                ->exists();
            if ($customRule) {
                $this->blocked('finance_posting_profile_rules.account_role_id');
            }
        }

        if ($accountIds !== []) {
            foreach ([
                ['finance_journal_lines', 'account_id'],
                ['finance_ledger_entries', 'account_id'],
                ['finance_account_balances', 'account_id'],
                ['finance_bank_accounts', 'account_id'],
                ['finance_budget_lines', 'account_id'],
                ['tax_transactions', 'account_id'],
            ] as [$table, $column]) {
                if (Schema::hasTable($table)
                    && Schema::hasColumn($table, $column)
                    && DB::table($table)->whereIn($column, $accountIds)->exists()) {
                    $this->blocked("{$table}.{$column}");
                }
            }
        }

        if (Schema::hasTable('finance_posting_profile_rules')) {
            DB::table('finance_posting_profile_rules')
                ->when($profileIds !== [], fn ($query) => $query->whereIn('posting_profile_id', $profileIds))
                ->when($profileIds === [] && $roleIds !== [], fn ($query) => $query->whereIn('account_role_id', $roleIds))
                ->delete();
        }

        if (Schema::hasTable('finance_account_assignments')) {
            DB::table('finance_account_assignments')
                ->where(function ($query) use ($roleIds, $accountIds): void {
                    if ($roleIds !== []) {
                        $query->whereIn('account_role_id', $roleIds);
                    }
                    if ($accountIds !== []) {
                        $roleIds === []
                            ? $query->whereIn('account_id', $accountIds)
                            : $query->orWhereIn('account_id', $accountIds);
                    }
                })
                ->delete();
        }

        if ($profileIds !== []) {
            DB::table('finance_posting_profiles')->whereIn('id', $profileIds)->delete();
        }
        if ($roleIds !== [] && Schema::hasTable('finance_account_roles')) {
            DB::table('finance_account_roles')->whereIn('id', $roleIds)->delete();
        }
        if ($accountIds !== [] && Schema::hasTable('finance_accounts')) {
            DB::table('finance_accounts')->whereIn('id', $accountIds)->delete();
        }
        if (Schema::hasTable('finance_account_categories')) {
            DB::table('finance_account_categories')->whereIn('code', self::FINANCE_CATEGORY_CODES)->delete();
        }
    }

    /** @param list<string> $values */
    private function assertNoValues(string $table, string $column, array $values): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if (DB::table($table)->whereIn($column, $values)->exists()) {
            $this->blocked("{$table}.{$column}");
        }
    }

    private function blocked(string $source): never
    {
        throw new RuntimeException(
            "Vehicle Rental cannot be removed while external financial history references [{$source}]. Archive or migrate those records before deploying this release.",
        );
    }
}