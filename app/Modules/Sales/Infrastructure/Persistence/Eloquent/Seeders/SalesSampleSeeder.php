<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SalesSampleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('sales_orders') || ! Schema::hasTable('customers')) {
            return;
        }

        $tenantId = $this->tenantId();
        if ($tenantId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            $organizationUnitId = $this->organizationUnitId($tenantId);
            $currencyId = $this->idFrom('currencies', null, ['code' => 'LKR']);
            $warehouseId = $this->idFrom('warehouses', $tenantId);
            $stockItemId = $this->idFrom('items', $tenantId, ['sku' => 'ITM-FILTER-001']);
            $serviceItemId = $this->idFrom('items', $tenantId, ['sku' => 'ITM-SERVICE-001']);
            $uomId = $stockItemId !== null
                ? (int) DB::table('items')->where('id', $stockItemId)->value('base_uom_id')
                : null;
            $serviceUomId = $serviceItemId !== null
                ? (int) DB::table('items')->where('id', $serviceItemId)->value('base_uom_id')
                : $uomId;

            if ($warehouseId === null || $stockItemId === null || $serviceItemId === null || $uomId === null) {
                return;
            }

            $customerId = $this->seedCustomer($tenantId, $organizationUnitId, $currencyId);
            $salesOrderId = $this->seedSalesOrder($tenantId, $organizationUnitId, $customerId, $warehouseId, $currencyId);
            $this->seedSalesOrderLines($tenantId, $organizationUnitId, $salesOrderId, $stockItemId, $serviceItemId, $uomId, $serviceUomId);

            $gdnId = $this->seedGdn($tenantId, $organizationUnitId, $customerId, $warehouseId, $currencyId, $salesOrderId);
            $this->seedGdnLine($tenantId, $organizationUnitId, $gdnId, $salesOrderId, $stockItemId, $uomId, $warehouseId);

            $documentId = $this->seedInvoiceDocument($tenantId, $organizationUnitId, $customerId, $currencyId, $salesOrderId);
            if ($documentId !== null && Schema::hasTable('sales_document_links')) {
                DB::table('sales_document_links')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'source_type' => 'sales_order', 'source_id' => $salesOrderId, 'document_id' => $documentId, 'source_line_id' => null, 'document_line_id' => null],
                    [
                        'row_version' => 1,
                        'organization_unit_id' => $organizationUnitId,
                        'metadata' => json_encode(['seed_source' => 'sales_module']),
                        'linked_quantity' => 1,
                        'linked_amount' => 2250,
                        'status' => 'active',
                        'linked_at' => now(),
                        'created_by' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }

            $paymentId = $this->seedReceipt($tenantId, $organizationUnitId, $customerId, $currencyId);
            $this->seedAdvance($tenantId, $organizationUnitId, $customerId, $currencyId, $paymentId);
            $returnId = $this->seedReturn($tenantId, $organizationUnitId, $customerId, $currencyId, $salesOrderId, $gdnId, $documentId);
            $this->seedReturnLine($tenantId, $organizationUnitId, $returnId, $stockItemId, $uomId, $warehouseId);
            $this->seedLedgerNote($tenantId, $organizationUnitId, $salesOrderId);
        });
    }

    private function seedCustomer(int $tenantId, ?int $organizationUnitId, ?int $currencyId): int
    {
        DB::table('customers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'customer_code' => 'CUS-SALES-SEED'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'customer_name' => 'Sales Seed Customer',
                'legal_name' => 'Sales Seed Customer',
                'display_name' => 'Sales Seed Customer',
                'customer_type' => 'business',
                'category_id' => null,
                'registration_number' => 'SALES-SEED-CUSTOMER',
                'tax_number' => null,
                'vat_number' => null,
                'email' => 'sales-seed@example.test',
                'phone' => null,
                'mobile' => null,
                'website' => null,
                'default_currency_id' => $currencyId,
                'default_payment_term_id' => $this->idFrom('payment_terms', $tenantId),
                'default_receivable_account_id' => $this->accountId($tenantId, '1100'),
                'default_income_account_id' => $this->accountId($tenantId, '4000'),
                'credit_limit' => 100000,
                'credit_days' => 30,
                'credit_hold' => false,
                'status' => 'active',
                'is_active' => true,
                'notes' => 'Seed customer for Sales backend workflows.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('customers')->where('tenant_id', $tenantId)->where('customer_code', 'CUS-SALES-SEED')->value('id');
    }

    private function seedSalesOrder(int $tenantId, ?int $organizationUnitId, int $customerId, int $warehouseId, ?int $currencyId): int
    {
        DB::table('sales_orders')->updateOrInsert(
            ['tenant_id' => $tenantId, 'so_number' => 'SO-SEED-0001'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'reference' => 'SALES-SEED',
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'status' => 'approved',
                'invoice_status' => 'partially_invoiced',
                'reservation_status' => 'not_reserved',
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'order_date' => now()->toDateString(),
                'requested_delivery_date' => now()->addDays(3)->toDateString(),
                'price_list_id' => $this->idFrom('price_lists', $tenantId, ['type' => 'sales']),
                'ordered_qty_total' => 3,
                'delivered_qty_total' => 1,
                'invoiced_qty_total' => 1,
                'outstanding_qty_total' => 2,
                'subtotal' => 2500,
                'line_tax_total' => 0,
                'line_discount_total' => 250,
                'discount_total' => 250,
                'tax_total' => 0,
                'grand_total' => 2250,
                'paid_amount' => 1000,
                'balance' => 1250,
                'tax_account_id' => $this->accountId($tenantId, '2100'),
                'discount_account_id' => $this->accountId($tenantId, '5000'),
                'sales_account_id' => $this->accountId($tenantId, '4000'),
                'notes' => 'Seed sales order.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('sales_orders')->where('tenant_id', $tenantId)->where('so_number', 'SO-SEED-0001')->value('id');
    }

    private function seedSalesOrderLines(int $tenantId, ?int $organizationUnitId, int $salesOrderId, int $stockItemId, int $serviceItemId, int $uomId, ?int $serviceUomId): void
    {
        foreach ([
            ['item_id' => $stockItemId, 'uom_id' => $uomId, 'description' => 'Seed stock item sale', 'qty' => 2, 'unit_price' => 1000, 'unit_cost' => 600, 'discount' => 100],
            ['item_id' => $serviceItemId, 'uom_id' => $serviceUomId ?? $uomId, 'description' => 'Seed service sale', 'qty' => 1, 'unit_price' => 500, 'unit_cost' => 0, 'discount' => 150],
        ] as $line) {
            $gross = $line['qty'] * $line['unit_price'];
            $net = $gross - $line['discount'];
            DB::table('sales_order_lines')->updateOrInsert(
                ['tenant_id' => $tenantId, 'sales_order_id' => $salesOrderId, 'item_id' => $line['item_id']],
                [
                    'row_version' => 1,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => json_encode(['seed_source' => 'sales_module']),
                    'reference' => 'SALES-SEED',
                    'variant_id' => null,
                    'batch_id' => null,
                    'serial_id' => null,
                    'warehouse_id' => null,
                    'location_id' => null,
                    'description' => $line['description'],
                    'uom_id' => $line['uom_id'],
                    'ordered_qty' => $line['qty'],
                    'ordered_base_qty' => $line['qty'],
                    'reserved_qty' => 0,
                    'picked_qty' => 0,
                    'delivered_qty' => $line['item_id'] === $stockItemId ? 1 : 0,
                    'rejected_qty' => 0,
                    'invoiced_qty' => $line['item_id'] === $stockItemId ? 1 : 0,
                    'returned_qty' => 0,
                    'cancelled_qty' => 0,
                    'outstanding_qty' => $line['item_id'] === $stockItemId ? 1 : $line['qty'],
                    'reservation_status' => 'not_reserved',
                    'delivery_status' => $line['item_id'] === $stockItemId ? 'partially_delivered' : 'not_delivered',
                    'document_status' => $line['item_id'] === $stockItemId ? 'partially_documented' : 'not_documented',
                    'unit_price' => $line['unit_price'],
                    'unit_cost' => $line['unit_cost'],
                    'discount_type' => 'fixed',
                    'discount_value' => $line['discount'],
                    'discount_amount' => $line['discount'],
                    'gross_amount' => $gross,
                    'line_total' => $net,
                    'tax_group_id' => null,
                    'tax_amount' => 0,
                    'line_total_with_tax' => $net,
                    'account_id' => $this->accountId($tenantId, '4000'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedGdn(int $tenantId, ?int $organizationUnitId, int $customerId, int $warehouseId, ?int $currencyId, int $salesOrderId): int
    {
        DB::table('gdn_headers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'gdn_number' => 'GDN-SEED-0001'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'reference' => 'SALES-SEED',
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'sales_order_id' => $salesOrderId,
                'status' => 'posted',
                'invoice_status' => 'partially_invoiced',
                'picking_status' => 'completed',
                'delivery_status' => 'delivered',
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'delivered_date' => now()->toDateString(),
                'expected_qty_total' => 1,
                'picked_qty_total' => 1,
                'delivered_qty_total' => 1,
                'subtotal' => 1000,
                'line_discount_total' => 100,
                'discount_total' => 100,
                'grand_total' => 900,
                'tax_account_id' => $this->accountId($tenantId, '2100'),
                'discount_account_id' => $this->accountId($tenantId, '5000'),
                'gdn_account_id' => $this->accountId($tenantId, '4000'),
                'notes' => 'Seed goods delivery note.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('gdn_headers')->where('tenant_id', $tenantId)->where('gdn_number', 'GDN-SEED-0001')->value('id');
    }

    private function seedGdnLine(int $tenantId, ?int $organizationUnitId, int $gdnId, int $salesOrderId, int $itemId, int $uomId, int $warehouseId): void
    {
        $salesOrderLineId = (int) DB::table('sales_order_lines')->where('tenant_id', $tenantId)->where('sales_order_id', $salesOrderId)->where('item_id', $itemId)->value('id');
        DB::table('gdn_lines')->updateOrInsert(
            ['tenant_id' => $tenantId, 'gdn_header_id' => $gdnId, 'item_id' => $itemId],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'reference' => 'SALES-SEED',
                'sales_order_line_id' => $salesOrderLineId ?: null,
                'variant_id' => null,
                'batch_id' => null,
                'serial_id' => null,
                'warehouse_id' => $warehouseId,
                'location_id' => null,
                'description' => 'Seed delivered stock line',
                'uom_id' => $uomId,
                'expected_qty' => 1,
                'picked_qty' => 1,
                'delivered_qty' => 1,
                'delivered_base_qty' => 1,
                'short_qty' => 0,
                'rejected_qty' => 0,
                'invoiced_qty' => 1,
                'returned_qty' => 0,
                'picking_status' => 'completed',
                'delivery_status' => 'delivered',
                'unit_price' => 1000,
                'unit_cost' => 600,
                'discount_type' => 'fixed',
                'discount_value' => 100,
                'discount_amount' => 100,
                'gross_amount' => 1000,
                'line_total' => 900,
                'tax_group_id' => null,
                'tax_amount' => 0,
                'line_total_with_tax' => 900,
                'account_id' => $this->accountId($tenantId, '4000'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedInvoiceDocument(int $tenantId, ?int $organizationUnitId, int $customerId, ?int $currencyId, int $salesOrderId): ?int
    {
        if (! Schema::hasTable('documents')) {
            return null;
        }

        $definitionId = $this->documentDefinitionId($tenantId, 'SALES_INVOICE');
        $typeId = $definitionId !== null
            ? (int) DB::table('document_definitions')->where('id', $definitionId)->value('document_type_id')
            : (int) DB::table('document_types')->where('tenant_id', $tenantId)->where('code', 'SALES_INVOICE')->value('id');
        if ($typeId < 1) {
            return null;
        }

        DB::table('documents')->updateOrInsert(
            ['tenant_id' => $tenantId, 'document_number' => 'SINV-SEED-0001'],
            [
                'organization_unit_id' => $organizationUnitId,
                'document_type_id' => $typeId,
                'document_definition_id' => $definitionId,
                'status' => 'posted',
                'version' => 1,
                'party_id' => $customerId,
                'source_module' => 'sales',
                'source_type' => 'sales_order',
                'source_id' => $salesOrderId,
                'source_reference' => 'SO-SEED-0001',
                'title' => 'Seed Sales Invoice',
                'document_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'currency_id' => $currencyId,
                'subtotal' => 2500,
                'discount_total' => 250,
                'tax_total' => 0,
                'grand_total' => 2250,
                'notes' => 'Seed sales invoice document.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('documents')->where('tenant_id', $tenantId)->where('document_number', 'SINV-SEED-0001')->value('id');
    }

    private function seedReceipt(int $tenantId, ?int $organizationUnitId, int $customerId, ?int $currencyId): ?int
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('payment_methods')) {
            return null;
        }

        $methodId = $this->idFrom('payment_methods', $tenantId, ['code' => 'CASH']);
        if ($methodId === null) {
            return null;
        }

        DB::table('payments')->updateOrInsert(
            ['tenant_id' => $tenantId, 'payment_number' => 'REC-SALES-SEED-0001'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'party_type' => 'customer',
                'party_id' => $customerId,
                'party_role' => 'payer',
                'payer_type' => 'customer',
                'payer_id' => $customerId,
                'source_module' => 'sales',
                'source_type' => 'sales_receipt',
                'source_reference' => 'SINV-SEED-0001',
                'reference' => 'SINV-SEED-0001',
                'payment_date' => now()->toDateString(),
                'amount' => 1000,
                'allocated_amount' => 1000,
                'direction' => 'inbound',
                'payment_method_id' => $methodId,
                'account_id' => $this->accountId($tenantId, '1000'),
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'base_amount' => 1000,
                'status' => 'posted',
                'notes' => 'Seed sales receipt.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('payments')->where('tenant_id', $tenantId)->where('payment_number', 'REC-SALES-SEED-0001')->value('id');
    }

    private function seedAdvance(int $tenantId, ?int $organizationUnitId, int $customerId, ?int $currencyId, ?int $paymentId): void
    {
        if (! Schema::hasTable('advance_payments')) {
            return;
        }

        DB::table('advance_payments')->updateOrInsert(
            ['tenant_id' => $tenantId, 'advance_number' => 'ADV-SALES-SEED-0001'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'party_type' => 'customer',
                'party_id' => $customerId,
                'reference' => 'ADV-SALES-SEED',
                'amount' => 500,
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'base_amount' => 500,
                'remaining_amount' => 500,
                'advance_date' => now()->toDateString(),
                'type' => 'customer',
                'status' => 'open',
                'payment_id' => $paymentId,
                'notes' => 'Seed customer advance.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedReturn(int $tenantId, ?int $organizationUnitId, int $customerId, ?int $currencyId, int $salesOrderId, int $gdnId, ?int $documentId): int
    {
        DB::table('sales_returns')->updateOrInsert(
            ['tenant_id' => $tenantId, 'return_number' => 'SRET-SEED-0001'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'reference' => 'SALES-SEED-RETURN',
                'customer_id' => $customerId,
                'original_sales_order_id' => $salesOrderId,
                'original_gdn_id' => $gdnId,
                'original_document_id' => $documentId,
                'status' => 'approved',
                'refund_status' => 'not_refunded',
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'return_date' => now()->toDateString(),
                'return_reason' => 'seed_return',
                'return_qty_total' => 1,
                'restocked_qty_total' => 1,
                'subtotal' => 1000,
                'line_discount_total' => 100,
                'discount_total' => 100,
                'grand_total' => 900,
                'tax_account_id' => $this->accountId($tenantId, '2100'),
                'discount_account_id' => $this->accountId($tenantId, '5000'),
                'sales_return_account_id' => $this->accountId($tenantId, '4000'),
                'notes' => 'Seed sales return.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('sales_returns')->where('tenant_id', $tenantId)->where('return_number', 'SRET-SEED-0001')->value('id');
    }

    private function seedReturnLine(int $tenantId, ?int $organizationUnitId, int $returnId, int $itemId, int $uomId, int $warehouseId): void
    {
        DB::table('sales_return_lines')->updateOrInsert(
            ['tenant_id' => $tenantId, 'sales_return_id' => $returnId, 'item_id' => $itemId],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'reference' => 'SALES-SEED-RETURN',
                'original_gdn_line_id' => null,
                'original_sales_order_line_id' => null,
                'variant_id' => null,
                'batch_id' => null,
                'serial_id' => null,
                'warehouse_id' => $warehouseId,
                'location_id' => null,
                'description' => 'Seed return stock line',
                'uom_id' => $uomId,
                'return_qty' => 1,
                'return_base_qty' => 1,
                'restock_qty' => 1,
                'scrap_qty' => 0,
                'refund_qty' => 1,
                'write_off_qty' => 0,
                'unit_price' => 1000,
                'unit_cost' => 600,
                'restocking_fee' => 0,
                'condition' => 'good',
                'disposition' => 'restock',
                'discount_type' => 'fixed',
                'discount_value' => 100,
                'discount_amount' => 100,
                'gross_amount' => 1000,
                'line_total' => 900,
                'tax_group_id' => null,
                'tax_amount' => 0,
                'line_total_with_tax' => 900,
                'account_id' => $this->accountId($tenantId, '4000'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedLedgerNote(int $tenantId, ?int $organizationUnitId, int $salesOrderId): void
    {
        if (! Schema::hasTable('sales_ledger_notes')) {
            return;
        }

        DB::table('sales_ledger_notes')->updateOrInsert(
            ['tenant_id' => $tenantId, 'source_type' => 'sales_order', 'source_id' => $salesOrderId, 'note_type' => 'workflow'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => json_encode(['seed_source' => 'sales_module']),
                'source_reference' => 'SO-SEED-0001',
                'body' => 'Seed Sales workflow note.',
                'is_visible_to_api' => true,
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
        $id = (int) DB::table('organization_units')->where('tenant_id', $tenantId)->where('code', 'MAIN')->value('id');
        $id = $id > 0 ? $id : (int) DB::table('organization_units')->where('tenant_id', $tenantId)->value('id');

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

    private function accountId(int $tenantId, string $code): ?int
    {
        return $this->idFrom('accounts', $tenantId, ['code' => $code]);
    }

    private function documentDefinitionId(int $tenantId, string $code): ?int
    {
        return $this->idFrom('document_definitions', $tenantId, ['definition_code' => $code]);
    }
}
