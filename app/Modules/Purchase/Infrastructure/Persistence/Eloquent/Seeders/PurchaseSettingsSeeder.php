<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PurchaseSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('purchase_settings') || ! Schema::hasTable('tenants')) {
            return;
        }

        $tenantId = $this->tenantId();
        if ($tenantId === null) {
            return;
        }

        $organizationUnitId = $this->organizationUnitId($tenantId);
        $account = fn (string $code): ?int => $this->accountId($tenantId, $code);

        DB::table('purchase_settings')->updateOrInsert(
            ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId],
            [
                'row_version' => 1,
                'metadata' => json_encode(['seed_source' => 'purchase_module']),
                'default_supplier_payable_account_id' => $account('2000'),
                'default_purchase_account_id' => $account('5000'),
                'default_inventory_account_id' => $account('1000'),
                'default_purchase_discount_account_id' => $account('5000'),
                'default_purchase_tax_account_id' => $account('2100'),
                'default_freight_account_id' => $account('5000'),
                'default_return_account_id' => $account('5000'),
                'default_rounding_account_id' => $account('5000'),
                'default_write_off_account_id' => $account('5000'),
                'default_advance_payment_account_id' => $account('1000'),
                'default_refund_account_id' => $account('1000'),
                'default_payment_term_id' => $this->idFrom('payment_terms', $tenantId),
                'default_currency_id' => $this->idFrom('currencies', null, ['code' => 'LKR']),
                'default_warehouse_id' => $this->idFrom('warehouses', $tenantId),
                'default_price_list_id' => $this->idFrom('price_lists', $tenantId, ['type' => 'purchase']),
                'default_tax_group_id' => $this->idFrom('tax_groups', $tenantId),
                'purchase_order_document_definition_id' => $this->documentDefinitionId($tenantId, 'PURCHASE_ORDER'),
                'grn_document_definition_id' => $this->documentDefinitionId($tenantId, 'GOODS_RECEIVED_NOTE'),
                'purchase_invoice_document_definition_id' => $this->documentDefinitionId($tenantId, 'PURCHASE_INVOICE'),
                'purchase_return_document_definition_id' => $this->documentDefinitionId($tenantId, 'PURCHASE_RETURN'),
                'require_po_before_grn' => false,
                'require_grn_before_invoice' => false,
                'allow_direct_grn' => true,
                'allow_direct_purchase_document' => true,
                'allow_return_without_original' => true,
                'allow_negative_stock_on_return' => false,
                'allow_header_discount' => true,
                'allow_line_discount' => true,
                'tax_calculation_level' => 'line',
                'header_discount_allocation_method' => 'proportional',
                'default_po_status' => 'draft',
                'default_grn_status' => 'draft',
                'default_document_status' => 'draft',
                'default_return_status' => 'draft',
                'numbering_sequence_code' => 'PURCHASE',
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
