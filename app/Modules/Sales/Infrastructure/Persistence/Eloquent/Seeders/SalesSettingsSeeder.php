<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SalesSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('sales_settings') || ! Schema::hasTable('tenants')) {
            return;
        }

        $tenantId = $this->tenantId();
        if ($tenantId === null) {
            return;
        }

        $organizationUnitId = $this->organizationUnitId($tenantId);
        $account = fn (string $code): ?int => $this->accountId($tenantId, $code);

        DB::table('sales_settings')->updateOrInsert(
            ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId],
            [
                'row_version' => 1,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'default_customer_receivable_account_id' => $account('1100'),
                'default_sales_income_account_id' => $account('4000'),
                'default_inventory_account_id' => $account('1000'),
                'default_cogs_account_id' => $account('5000'),
                'default_sales_tax_account_id' => $account('2100'),
                'default_sales_discount_account_id' => $account('5000'),
                'default_return_account_id' => $account('4000'),
                'default_rounding_account_id' => $account('5000'),
                'default_write_off_account_id' => $account('5000'),
                'default_customer_advance_account_id' => $account('2000'),
                'default_refund_account_id' => $account('1000'),
                'default_payment_term_id' => $this->idFrom('payment_terms', $tenantId),
                'default_currency_id' => $this->idFrom('currencies', null, ['code' => 'LKR']),
                'default_warehouse_id' => $this->idFrom('warehouses', $tenantId),
                'default_price_list_id' => $this->idFrom('price_lists', $tenantId, ['type' => 'sales']),
                'default_tax_group_id' => $this->idFrom('tax_groups', $tenantId),
                'sales_order_document_definition_id' => $this->documentDefinitionId($tenantId, 'SALES_ORDER'),
                'gdn_document_definition_id' => $this->documentDefinitionId($tenantId, 'GOODS_DELIVERY_NOTE'),
                'sales_invoice_document_definition_id' => $this->documentDefinitionId($tenantId, 'SALES_INVOICE'),
                'sales_return_document_definition_id' => $this->documentDefinitionId($tenantId, 'SALES_RETURN'),
                'require_sales_order_before_gdn' => false,
                'require_gdn_before_invoice' => false,
                'allow_direct_gdn' => true,
                'allow_direct_sales_invoice' => true,
                'allow_return_without_original' => true,
                'reserve_stock_on_order' => false,
                'issue_stock_on_gdn' => true,
                'issue_stock_on_invoice' => false,
                'allow_header_discount' => true,
                'allow_line_discount' => true,
                'tax_calculation_level' => 'line',
                'header_discount_allocation_method' => 'proportional',
                'default_sales_order_status' => 'draft',
                'default_gdn_status' => 'draft',
                'default_sales_invoice_status' => 'draft',
                'default_sales_return_status' => 'draft',
                'numbering_sequence_code' => 'SALES',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function tenantId(): ?int
    {
        $id = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $id = $id > 0 ? $id : (int) DB::table('tenants')->value('id');

        return $id > 0 ? $id : null;
    }

    private function organizationUnitId(int $tenantId): ?int
    {
        if (! Schema::hasTable('organization_units')) {
            return null;
        }

        $id = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');
        $id = $id > 0 ? $id : (int) DB::table('organization_units')->where('tenant_id', $tenantId)->value('id');

        return $id > 0 ? $id : null;
    }

    private function accountId(int $tenantId, string $code): ?int
    {
        if (! Schema::hasTable('accounts')) {
            return null;
        }

        $id = (int) DB::table('accounts')->where('tenant_id', $tenantId)->where('code', $code)->value('id');

        return $id > 0 ? $id : null;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function idFrom(string $table, ?int $tenantId = null, array $extra = []): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);
        if ($tenantId !== null && Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        foreach ($extra as $field => $value) {
            $query->where($field, $value);
        }

        $id = (int) $query->value('id');

        return $id > 0 ? $id : null;
    }

    private function documentDefinitionId(int $tenantId, string $code): ?int
    {
        if (! Schema::hasTable('document_definitions')) {
            return null;
        }

        $id = (int) DB::table('document_definitions')
            ->where('tenant_id', $tenantId)
            ->where('definition_code', $code)
            ->value('id');

        return $id > 0 ? $id : null;
    }
}
