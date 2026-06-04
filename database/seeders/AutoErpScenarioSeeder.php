<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsAutoErpData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AutoErpScenarioSeeder extends Seeder
{
    use SeedsAutoErpData;

    public function run(): void
    {
        $tenantId = $this->defaultTenantId();
        if ($tenantId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            $context = $this->context($tenantId);
            if ($context === null) {
                return;
            }

            $this->seedSequences($context);
            $purchase = $this->seedPurchaseFlow($context);
            $sales = $this->seedSalesFlow($context);
            $this->seedInvoiceEdges($context, $sales);
            $this->seedVehiclePartyLinks($context);
            $this->seedExtensionAndAuditEvidence($context, $purchase, $sales);
        }, 3);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function context(int $tenantId): ?array
    {
        $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);
        $item = Schema::hasTable('items')
            ? DB::table('items')->where('tenant_id', $tenantId)->where('sku', 'ITM-FILTER-001')->first()
            : null;
        $serviceItem = Schema::hasTable('items')
            ? DB::table('items')->where('tenant_id', $tenantId)->where('sku', 'ITM-SERVICE-001')->first()
            : null;
        $customerId = $this->idBy('customers', ['tenant_id' => $tenantId, 'customer_code' => 'CUST-DEMO-FLEET']);
        $blockedCustomerId = $this->idBy('customers', ['tenant_id' => $tenantId, 'customer_code' => 'CUST-DEMO-HOLD']);
        $supplierId = $this->idBy('suppliers', ['tenant_id' => $tenantId, 'supplier_code' => 'SUP-DEMO-PARTS']);
        $warehouseId = $this->idBy('warehouses', ['tenant_id' => $tenantId, 'name' => 'Main Warehouse']);

        if ($item === null || $customerId === null || $supplierId === null || $warehouseId === null) {
            return null;
        }

        $locationId = $this->idBy('warehouse_locations', [
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'name' => 'Main Bin',
        ]);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $this->defaultUserId($tenantId),
            'currency_id' => $this->currencyId(),
            'currency_code' => $this->currencyCode(),
            'customer_id' => $customerId,
            'blocked_customer_id' => $blockedCustomerId ?? $customerId,
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
            'item_id' => (int) $item->id,
            'item_uom_id' => (int) $item->base_uom_id,
            'service_item_id' => $serviceItem === null ? (int) $item->id : (int) $serviceItem->id,
            'service_uom_id' => $serviceItem === null ? (int) $item->base_uom_id : (int) $serviceItem->base_uom_id,
            'tax_group_id' => $this->taxGroupId($tenantId),
            'sales_price_list_id' => $this->idBy('price_lists', ['tenant_id' => $tenantId, 'code' => 'PL-SALES-STD']),
            'purchase_price_list_id' => $this->idBy('price_lists', ['tenant_id' => $tenantId, 'code' => 'PL-PURCHASE-SUP']),
            'payment_term_id' => $this->paymentTermId($tenantId),
            'sales_account_id' => $this->accountId($tenantId, '4000'),
            'purchase_account_id' => $this->accountId($tenantId, '5000'),
            'receivable_account_id' => $this->accountId($tenantId, '1100'),
            'payable_account_id' => $this->accountId($tenantId, '2000'),
            'cash_account_id' => $this->accountId($tenantId, '1000'),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function seedSequences(array $context): void
    {
        foreach ([
            ['SALES_ORDER', 'SO-', 120],
            ['PURCHASE_ORDER', 'PO-', 95],
            ['SALES_INVOICE', 'SI-', 80],
            ['PURCHASE_INVOICE', 'PI-', 70],
            ['PAYMENT', 'PAY-', 60],
        ] as [$documentType, $prefix, $nextNumber]) {
            $this->upsert('sequences', [
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'document_type' => $documentType,
                'period_value' => '2026',
            ], [
                'metadata' => $this->seedMetadata('autoerp_scenario', 'sequence'),
                'prefix' => $prefix,
                'suffix' => '',
                'padding' => 5,
                'next_number' => $nextNumber,
                'period_type' => 'yearly',
                'created_by' => $context['user_id'],
                'updated_by' => $context['user_id'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, int|null>
     */
    private function seedPurchaseFlow(array $context): array
    {
        $tenantId = (int) $context['tenant_id'];
        $poId = null;
        $poLineId = null;
        $grnId = null;
        $grnLineId = null;
        $invoiceId = null;

        if (Schema::hasTable('purchase_orders')) {
            $this->upsert('purchase_orders', [
                'tenant_id' => $tenantId,
                'po_number' => 'PO-DEMO-0001',
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'purchase_confirmed'),
                'reference' => 'SUP-QUOTE-2026-001',
                'supplier_id' => $context['supplier_id'],
                'warehouse_id' => $context['warehouse_id'],
                'status' => 'partially_received',
                'document_status' => 'partially_documented',
                'currency_id' => $context['currency_id'],
                'exchange_rate' => '1.0000',
                'order_date' => '2026-03-01',
                'expected_date' => '2026-03-08',
                'price_list_id' => $context['purchase_price_list_id'],
                'payment_term_id' => $context['payment_term_id'],
                'subtotal' => '150000.0000',
                'line_tax_total' => '0.0000',
                'line_discount_total' => '5000.0000',
                'header_discount_type' => null,
                'header_discount_value' => null,
                'header_discount_amount' => '0.0000',
                'header_tax_group_id' => $context['tax_group_id'],
                'header_tax_amount' => '0.0000',
                'discount_total' => '5000.0000',
                'tax_total' => '0.0000',
                'debit_note_total' => '0.0000',
                'credit_note_total' => '0.0000',
                'grand_total' => '145000.0000',
                'paid_amount' => '25000.0000',
                'balance' => '120000.0000',
                'tax_account_id' => null,
                'discount_account_id' => $context['purchase_account_id'],
                'purchase_account_id' => $context['purchase_account_id'],
                'notes' => 'Seeded partially received purchase order.',
                'created_by' => $context['user_id'],
                'updated_by' => $context['user_id'],
                'submitted_by' => $context['user_id'],
                'approved_by' => $context['user_id'],
                'confirmed_by' => $context['user_id'],
                'submitted_at' => '2026-03-01 09:00:00',
                'approved_at' => '2026-03-01 10:00:00',
                'confirmed_at' => '2026-03-01 11:00:00',
            ]);

            $poId = $this->idBy('purchase_orders', ['tenant_id' => $tenantId, 'po_number' => 'PO-DEMO-0001']);
        }

        if ($poId !== null && Schema::hasTable('purchase_order_lines')) {
            $this->upsert('purchase_order_lines', [
                'tenant_id' => $tenantId,
                'purchase_order_id' => $poId,
                'item_id' => $context['item_id'],
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'purchase_line'),
                'reference' => 'PO-DEMO-0001-1',
                'variant_id' => null,
                'description' => 'Oil filter replenishment',
                'uom_id' => $context['item_uom_id'],
                'ordered_qty' => '12.0000',
                'received_qty' => '6.0000',
                'rejected_qty' => '1.0000',
                'returned_qty' => '0.0000',
                'documented_qty' => '6.0000',
                'unit_price' => '12500.0000',
                'discount_type' => 'fixed',
                'discount_value' => '5000.0000',
                'discount_amount' => '5000.0000',
                'gross_amount' => '150000.0000',
                'line_total' => '145000.0000',
                'tax_group_id' => $context['tax_group_id'],
                'tax_amount' => '0.0000',
                'line_total_with_tax' => '145000.0000',
                'account_id' => $context['purchase_account_id'],
            ]);

            $poLineId = DB::table('purchase_order_lines')
                ->where('tenant_id', $tenantId)
                ->where('purchase_order_id', $poId)
                ->where('item_id', $context['item_id'])
                ->value('id');
            $poLineId = $poLineId === null ? null : (int) $poLineId;
        }

        if (Schema::hasTable('grn_headers')) {
            $this->upsert('grn_headers', [
                'tenant_id' => $tenantId,
                'grn_number' => 'GRN-DEMO-0001',
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'grn_posted'),
                'reference' => 'SUP-DELIVERY-2026-001',
                'supplier_id' => $context['supplier_id'],
                'warehouse_id' => $context['warehouse_id'],
                'purchase_order_id' => $poId,
                'status' => 'posted',
                'document_status' => 'partially_documented',
                'inspection_status' => 'inspected',
                'putaway_status' => 'completed',
                'currency_id' => $context['currency_id'],
                'exchange_rate' => '1.0000',
                'received_date' => '2026-03-05',
                'price_list_id' => $context['purchase_price_list_id'],
                'subtotal' => '75000.0000',
                'line_tax_total' => '0.0000',
                'line_discount_total' => '0.0000',
                'discount_total' => '0.0000',
                'tax_total' => '0.0000',
                'debit_note_total' => '0.0000',
                'credit_note_total' => '0.0000',
                'grand_total' => '75000.0000',
                'tax_account_id' => null,
                'discount_account_id' => null,
                'grn_account_id' => $context['purchase_account_id'],
                'notes' => 'Seeded GRN with one rejected quantity.',
                'created_by' => $context['user_id'],
                'updated_by' => $context['user_id'],
                'submitted_by' => $context['user_id'],
                'confirmed_by' => $context['user_id'],
                'posted_by' => $context['user_id'],
                'submitted_at' => '2026-03-05 09:00:00',
                'confirmed_at' => '2026-03-05 10:00:00',
                'posted_at' => '2026-03-05 11:00:00',
            ]);

            $grnId = $this->idBy('grn_headers', ['tenant_id' => $tenantId, 'grn_number' => 'GRN-DEMO-0001']);
        }

        if ($grnId !== null && Schema::hasTable('grn_lines')) {
            $this->upsert('grn_lines', [
                'tenant_id' => $tenantId,
                'grn_header_id' => $grnId,
                'item_id' => $context['item_id'],
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'grn_line'),
                'reference' => 'GRN-DEMO-0001-1',
                'purchase_order_line_id' => $poLineId,
                'variant_id' => null,
                'batch_id' => null,
                'serial_id' => null,
                'warehouse_id' => $context['warehouse_id'],
                'location_id' => $context['location_id'],
                'description' => 'Oil filter replenishment receipt',
                'uom_id' => $context['item_uom_id'],
                'expected_qty' => '6.0000',
                'received_qty' => '6.0000',
                'accepted_qty' => '5.0000',
                'rejected_qty' => '1.0000',
                'damaged_qty' => '0.0000',
                'inspected_qty' => '6.0000',
                'putaway_qty' => '5.0000',
                'returned_qty' => '0.0000',
                'documented_qty' => '6.0000',
                'unit_price' => '12500.0000',
                'discount_type' => null,
                'discount_value' => '0.0000',
                'discount_amount' => '0.0000',
                'gross_amount' => '75000.0000',
                'line_total' => '75000.0000',
                'tax_group_id' => $context['tax_group_id'],
                'tax_amount' => '0.0000',
                'line_total_with_tax' => '75000.0000',
                'account_id' => $context['purchase_account_id'],
            ]);

            $grnLineId = DB::table('grn_lines')
                ->where('tenant_id', $tenantId)
                ->where('grn_header_id', $grnId)
                ->where('item_id', $context['item_id'])
                ->value('id');
            $grnLineId = $grnLineId === null ? null : (int) $grnLineId;
        }

        if ($grnId !== null) {
            $this->statusHistory('purchase_status_histories', $context, 'purchase_order', $poId, 'confirmed', 'partially_received', 'Received first partial shipment.');
            $this->statusHistory('purchase_status_histories', $context, 'grn_header', $grnId, 'confirmed', 'posted', 'GRN posted from seed data.');
            $invoiceId = $this->seedInvoice($context, [
                'type_code' => 'PURCHASE_INVOICE',
                'invoice_number' => 'PI-DEMO-0001',
                'invoice_date' => '2026-03-06',
                'due_date' => '2026-04-05',
                'status' => 'partially_paid',
                'direction' => 'payable',
                'party_type' => 'supplier',
                'party_id' => $context['supplier_id'],
                'source_module' => 'purchase',
                'source_type' => 'grn_header',
                'source_id' => $grnId,
                'source_reference' => 'GRN-DEMO-0001',
                'line_item_id' => $context['item_id'],
                'line_uom_id' => $context['item_uom_id'],
                'line_description' => 'Oil filter replenishment invoice',
                'quantity' => '6.0000',
                'unit_price' => '12500.0000',
                'subtotal' => '75000.0000',
                'discount' => '0.0000',
                'tax' => '0.0000',
                'charge' => '0.0000',
                'total' => '75000.0000',
                'paid' => '25000.0000',
                'balance' => '50000.0000',
                'source_line_id' => $grnLineId,
            ]);
            $this->seedPaymentForInvoice($context, $invoiceId, 'PAY-DEMO-OUT-0001', 'outbound', '25000.0000');
            $this->financeTransaction('ap_transactions', $context, $invoiceId, 'supplier', 'BILL', '0.0000', '75000.0000', '75000.0000', '25000.0000', '50000.0000', 'OPEN', 'PI-DEMO-0001');
        }

        return [
            'po_id' => $poId,
            'po_line_id' => $poLineId,
            'grn_id' => $grnId,
            'grn_line_id' => $grnLineId,
            'invoice_id' => $invoiceId,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, int|null>
     */
    private function seedSalesFlow(array $context): array
    {
        $tenantId = (int) $context['tenant_id'];
        $soId = null;
        $soLineId = null;
        $gdnId = null;
        $gdnLineId = null;
        $invoiceId = null;

        if (Schema::hasTable('sales_orders')) {
            $this->upsert('sales_orders', [
                'tenant_id' => $tenantId,
                'so_number' => 'SO-DEMO-0001',
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'sales_confirmed'),
                'reference' => 'CUST-PO-2026-001',
                'customer_id' => $context['customer_id'],
                'warehouse_id' => $context['warehouse_id'],
                'status' => 'partially_delivered',
                'invoice_status' => 'partially_invoiced',
                'reservation_status' => 'partially_reserved',
                'currency_id' => $context['currency_id'],
                'exchange_rate' => '1.0000',
                'order_date' => '2026-03-10',
                'requested_delivery_date' => '2026-03-15',
                'price_list_id' => $context['sales_price_list_id'],
                'ordered_qty_total' => '4.0000',
                'reserved_qty_total' => '2.0000',
                'picked_qty_total' => '2.0000',
                'delivered_qty_total' => '2.0000',
                'invoiced_qty_total' => '2.0000',
                'returned_qty_total' => '0.0000',
                'cancelled_qty_total' => '0.0000',
                'outstanding_qty_total' => '2.0000',
                'subtotal' => '74000.0000',
                'line_tax_total' => '0.0000',
                'line_discount_total' => '0.0000',
                'discount_total' => '0.0000',
                'tax_total' => '0.0000',
                'debit_note_total' => '0.0000',
                'credit_note_total' => '0.0000',
                'grand_total' => '74000.0000',
                'paid_amount' => '10000.0000',
                'balance' => '64000.0000',
                'sales_account_id' => $context['sales_account_id'],
                'notes' => 'Seeded partially delivered sales order.',
                'created_by' => $context['user_id'],
                'updated_by' => $context['user_id'],
                'submitted_by' => $context['user_id'],
                'approved_by' => $context['user_id'],
                'confirmed_by' => $context['user_id'],
                'submitted_at' => '2026-03-10 09:00:00',
                'approved_at' => '2026-03-10 10:00:00',
                'confirmed_at' => '2026-03-10 11:00:00',
            ]);

            $soId = $this->idBy('sales_orders', ['tenant_id' => $tenantId, 'so_number' => 'SO-DEMO-0001']);
        }

        if ($soId !== null && Schema::hasTable('sales_order_lines')) {
            $this->upsert('sales_order_lines', [
                'tenant_id' => $tenantId,
                'sales_order_id' => $soId,
                'item_id' => $context['item_id'],
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'sales_line'),
                'reference' => 'SO-DEMO-0001-1',
                'variant_id' => null,
                'batch_id' => null,
                'serial_id' => null,
                'warehouse_id' => $context['warehouse_id'],
                'location_id' => $context['location_id'],
                'description' => 'Oil filter sale',
                'uom_id' => $context['item_uom_id'],
                'ordered_qty' => '4.0000',
                'ordered_base_qty' => '4.0000',
                'reserved_qty' => '2.0000',
                'picked_qty' => '2.0000',
                'delivered_qty' => '2.0000',
                'invoiced_qty' => '2.0000',
                'outstanding_qty' => '2.0000',
                'reservation_status' => 'partially_reserved',
                'delivery_status' => 'partially_delivered',
                'document_status' => 'partially_documented',
                'unit_price' => '18500.0000',
                'unit_cost' => '12500.0000',
                'discount_type' => null,
                'discount_value' => '0.0000',
                'discount_amount' => '0.0000',
                'gross_amount' => '74000.0000',
                'line_total' => '74000.0000',
                'tax_group_id' => $context['tax_group_id'],
                'tax_amount' => '0.0000',
                'line_total_with_tax' => '74000.0000',
                'account_id' => $context['sales_account_id'],
            ]);

            $soLineId = DB::table('sales_order_lines')
                ->where('tenant_id', $tenantId)
                ->where('sales_order_id', $soId)
                ->where('item_id', $context['item_id'])
                ->value('id');
            $soLineId = $soLineId === null ? null : (int) $soLineId;
        }

        if (Schema::hasTable('gdn_headers')) {
            $this->upsert('gdn_headers', [
                'tenant_id' => $tenantId,
                'gdn_number' => 'GDN-DEMO-0001',
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'gdn_delivered'),
                'reference' => 'DELIVERY-RUN-2026-001',
                'customer_id' => $context['customer_id'],
                'warehouse_id' => $context['warehouse_id'],
                'sales_order_id' => $soId,
                'status' => 'confirmed',
                'invoice_status' => 'partially_invoiced',
                'picking_status' => 'completed',
                'delivery_status' => 'delivered',
                'currency_id' => $context['currency_id'],
                'exchange_rate' => '1.0000',
                'delivered_date' => '2026-03-12',
                'price_list_id' => $context['sales_price_list_id'],
                'expected_qty_total' => '2.0000',
                'picked_qty_total' => '2.0000',
                'delivered_qty_total' => '2.0000',
                'short_qty_total' => '0.0000',
                'rejected_qty_total' => '0.0000',
                'returned_qty_total' => '0.0000',
                'subtotal' => '37000.0000',
                'line_tax_total' => '0.0000',
                'line_discount_total' => '0.0000',
                'discount_total' => '0.0000',
                'tax_total' => '0.0000',
                'debit_note_total' => '0.0000',
                'credit_note_total' => '0.0000',
                'grand_total' => '37000.0000',
                'gdn_account_id' => $context['sales_account_id'],
                'notes' => 'Seeded delivered GDN for partial invoice workflow.',
                'created_by' => $context['user_id'],
                'updated_by' => $context['user_id'],
                'submitted_by' => $context['user_id'],
                'approved_by' => $context['user_id'],
                'confirmed_by' => $context['user_id'],
                'posted_by' => $context['user_id'],
                'submitted_at' => '2026-03-12 09:00:00',
                'approved_at' => '2026-03-12 10:00:00',
                'confirmed_at' => '2026-03-12 11:00:00',
                'posted_at' => '2026-03-12 11:15:00',
            ]);

            $gdnId = $this->idBy('gdn_headers', ['tenant_id' => $tenantId, 'gdn_number' => 'GDN-DEMO-0001']);
        }

        if ($gdnId !== null && Schema::hasTable('gdn_lines')) {
            $this->upsert('gdn_lines', [
                'tenant_id' => $tenantId,
                'gdn_header_id' => $gdnId,
                'item_id' => $context['item_id'],
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'gdn_line'),
                'reference' => 'GDN-DEMO-0001-1',
                'sales_order_line_id' => $soLineId,
                'variant_id' => null,
                'batch_id' => null,
                'serial_id' => null,
                'warehouse_id' => $context['warehouse_id'],
                'location_id' => $context['location_id'],
                'description' => 'Oil filter delivery',
                'uom_id' => $context['item_uom_id'],
                'expected_qty' => '2.0000',
                'picked_qty' => '2.0000',
                'delivered_qty' => '2.0000',
                'delivered_base_qty' => '2.0000',
                'short_qty' => '0.0000',
                'rejected_qty' => '0.0000',
                'invoiced_qty' => '2.0000',
                'returned_qty' => '0.0000',
                'picking_status' => 'completed',
                'delivery_status' => 'delivered',
                'unit_price' => '18500.0000',
                'unit_cost' => '12500.0000',
                'discount_type' => null,
                'discount_value' => '0.0000',
                'discount_amount' => '0.0000',
                'gross_amount' => '37000.0000',
                'line_total' => '37000.0000',
                'tax_group_id' => $context['tax_group_id'],
                'tax_amount' => '0.0000',
                'line_total_with_tax' => '37000.0000',
                'account_id' => $context['sales_account_id'],
            ]);

            $gdnLineId = DB::table('gdn_lines')
                ->where('tenant_id', $tenantId)
                ->where('gdn_header_id', $gdnId)
                ->where('item_id', $context['item_id'])
                ->value('id');
            $gdnLineId = $gdnLineId === null ? null : (int) $gdnLineId;
        }

        if ($gdnId !== null) {
            $this->statusHistory('sales_status_histories', $context, 'sales_order', $soId, 'confirmed', 'partially_delivered', 'Delivered first partial shipment.');
            $this->statusHistory('sales_status_histories', $context, 'gdn_header', $gdnId, 'confirmed', 'delivered', 'GDN delivered from seed data.');
            $invoiceId = $this->seedInvoice($context, [
                'type_code' => 'SALES_INVOICE',
                'invoice_number' => 'SI-DEMO-0001',
                'invoice_date' => '2026-03-13',
                'due_date' => '2026-04-12',
                'status' => 'partially_paid',
                'direction' => 'receivable',
                'party_type' => 'customer',
                'party_id' => $context['customer_id'],
                'source_module' => 'sales',
                'source_type' => 'gdn_header',
                'source_id' => $gdnId,
                'source_reference' => 'GDN-DEMO-0001',
                'line_item_id' => $context['item_id'],
                'line_uom_id' => $context['item_uom_id'],
                'line_description' => 'Oil filter sales invoice',
                'quantity' => '2.0000',
                'unit_price' => '18500.0000',
                'subtotal' => '37000.0000',
                'discount' => '0.0000',
                'tax' => '0.0000',
                'charge' => '0.0000',
                'total' => '37000.0000',
                'paid' => '10000.0000',
                'balance' => '27000.0000',
                'source_line_id' => $gdnLineId,
            ]);
            $this->seedPaymentForInvoice($context, $invoiceId, 'PAY-DEMO-IN-0001', 'inbound', '10000.0000');
            $this->financeTransaction('ar_transactions', $context, $invoiceId, 'customer', 'BILL', '37000.0000', '0.0000', '37000.0000', '10000.0000', '27000.0000', 'OPEN', 'SI-DEMO-0001');
        }

        return [
            'so_id' => $soId,
            'so_line_id' => $soLineId,
            'gdn_id' => $gdnId,
            'gdn_line_id' => $gdnLineId,
            'invoice_id' => $invoiceId,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, int|null>  $sales
     */
    private function seedInvoiceEdges(array $context, array $sales): void
    {
        $overdueId = $this->seedInvoice($context, [
            'type_code' => 'SALES_INVOICE',
            'invoice_number' => 'SI-DEMO-OVERDUE',
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-02-14',
            'status' => 'posted',
            'direction' => 'receivable',
            'party_type' => 'customer',
            'party_id' => $context['blocked_customer_id'],
            'source_module' => 'sales',
            'source_type' => 'direct_sales',
            'source_id' => 9001,
            'source_reference' => 'DIRECT-SALE-OVERDUE',
            'line_item_id' => $context['service_item_id'],
            'line_uom_id' => $context['service_uom_id'],
            'line_description' => 'Overdue service invoice for collection testing',
            'quantity' => '4.0000',
            'unit_price' => '8000.0000',
            'subtotal' => '32000.0000',
            'discount' => '0.0000',
            'tax' => '0.0000',
            'charge' => '0.0000',
            'total' => '32000.0000',
            'paid' => '0.0000',
            'balance' => '32000.0000',
            'source_line_id' => null,
        ]);

        $cancelledId = $this->seedInvoice($context, [
            'type_code' => 'SALES_INVOICE',
            'invoice_number' => 'SI-DEMO-CANCELLED',
            'invoice_date' => '2026-02-20',
            'due_date' => '2026-03-22',
            'status' => 'cancelled',
            'direction' => 'receivable',
            'party_type' => 'customer',
            'party_id' => $context['customer_id'],
            'source_module' => 'sales',
            'source_type' => 'direct_sales',
            'source_id' => 9002,
            'source_reference' => 'DIRECT-SALE-CANCELLED',
            'line_item_id' => $context['service_item_id'],
            'line_uom_id' => $context['service_uom_id'],
            'line_description' => 'Cancelled invoice negative workflow',
            'quantity' => '1.0000',
            'unit_price' => '5000.0000',
            'subtotal' => '5000.0000',
            'discount' => '0.0000',
            'tax' => '0.0000',
            'charge' => '0.0000',
            'total' => '5000.0000',
            'paid' => '0.0000',
            'balance' => '0.0000',
            'source_line_id' => null,
        ]);

        $creditId = $this->seedInvoice($context, [
            'type_code' => 'SALES_INVOICE',
            'invoice_number' => 'SI-DEMO-CREDIT',
            'invoice_date' => '2026-03-18',
            'due_date' => '2026-03-18',
            'status' => 'posted',
            'direction' => 'receivable',
            'party_type' => 'customer',
            'party_id' => $context['customer_id'],
            'source_module' => 'sales',
            'source_type' => 'credit_adjustment',
            'source_id' => 9003,
            'source_reference' => 'CREDIT-ADJ-0001',
            'line_item_id' => null,
            'line_uom_id' => null,
            'line_description' => 'Goodwill credit adjustment',
            'quantity' => '1.0000',
            'unit_price' => '-2000.0000',
            'subtotal' => '-2000.0000',
            'discount' => '0.0000',
            'tax' => '0.0000',
            'charge' => '0.0000',
            'total' => '-2000.0000',
            'paid' => '0.0000',
            'balance' => '-2000.0000',
            'source_line_id' => null,
        ]);

        if (($sales['invoice_id'] ?? null) !== null && $creditId !== null) {
            $this->upsert('invoice_links', [
                'tenant_id' => $context['tenant_id'],
                'invoice_id' => $sales['invoice_id'],
                'linked_invoice_id' => $creditId,
                'link_type' => 'credit',
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'amount' => '2000.0000',
                'notes' => 'Seeded credit adjustment linked to the main sales invoice.',
            ]);
        }

        if ($overdueId !== null) {
            $this->financeTransaction('ar_transactions', $context, $overdueId, 'customer', 'BILL', '32000.0000', '0.0000', '32000.0000', '0.0000', '32000.0000', 'OVERDUE', 'SI-DEMO-OVERDUE');
        }

        if ($cancelledId !== null) {
            $this->invoiceStatus($context, $cancelledId, 'draft', 'cancelled', 'Cancelled before posting.');
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $invoice
     */
    private function seedInvoice(array $context, array $invoice): ?int
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasTable('invoice_types')) {
            return null;
        }

        $invoiceTypeId = $this->idBy('invoice_types', [
            'tenant_id' => $context['tenant_id'],
            'code' => $invoice['type_code'],
        ]);
        if ($invoiceTypeId === null) {
            return null;
        }

        $this->upsert('invoices', [
            'tenant_id' => $context['tenant_id'],
            'invoice_type_id' => $invoiceTypeId,
            'invoice_number' => $invoice['invoice_number'],
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'invoice_date' => $invoice['invoice_date'],
            'due_date' => $invoice['due_date'],
            'status' => $invoice['status'],
            'direction' => $invoice['direction'],
            'party_type' => $invoice['party_type'],
            'party_id' => $invoice['party_id'],
            'billing_party_type' => $invoice['party_type'],
            'billing_party_id' => $invoice['party_id'],
            'currency_code' => $context['currency_code'],
            'exchange_rate' => '1.0000',
            'subtotal_amount' => $invoice['subtotal'],
            'discount_amount' => $invoice['discount'],
            'tax_amount' => $invoice['tax'],
            'charge_amount' => $invoice['charge'],
            'rounding_amount' => '0.0000',
            'total_amount' => $invoice['total'],
            'paid_amount' => $invoice['paid'],
            'credited_amount' => $invoice['invoice_number'] === 'SI-DEMO-CREDIT' ? '2000.0000' : '0.0000',
            'balance_amount' => $invoice['balance'],
            'source_module' => $invoice['source_module'],
            'source_type' => $invoice['source_type'],
            'source_id' => $invoice['source_id'],
            'source_reference' => $invoice['source_reference'],
            'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
            'schema_version' => 1,
            'data_json' => $this->json(['scenario' => 'demo', 'calculation_mode' => 'seeded']),
            'metadata_json' => $this->json(['seed_source' => 'autoerp_scenario']),
            'posted_at' => in_array($invoice['status'], ['posted', 'partially_paid', 'paid'], true) ? $invoice['invoice_date'].' 12:00:00' : null,
            'posted_by' => $context['user_id'],
            'created_by' => $context['user_id'],
            'updated_by' => $context['user_id'],
        ]);

        $invoiceId = $this->idBy('invoices', [
            'tenant_id' => $context['tenant_id'],
            'invoice_type_id' => $invoiceTypeId,
            'invoice_number' => $invoice['invoice_number'],
        ]);
        if ($invoiceId === null) {
            return null;
        }

        $lineType = $invoice['line_item_id'] === null ? 'discount' : 'item';
        $this->upsert('invoice_lines', [
            'tenant_id' => $context['tenant_id'],
            'invoice_id' => $invoiceId,
            'line_no' => 1,
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'line_type' => $lineType,
            'item_id' => $invoice['line_item_id'],
            'item_variant_id' => null,
            'description' => $invoice['line_description'],
            'uom_id' => $invoice['line_uom_id'],
            'quantity' => $invoice['quantity'],
            'unit_price' => $invoice['unit_price'],
            'discount_type' => null,
            'discount_value' => '0.0000',
            'discount_amount' => '0.0000',
            'tax_group_id' => $context['tax_group_id'],
            'tax_amount' => $invoice['tax'],
            'line_subtotal' => $invoice['subtotal'],
            'line_total' => $invoice['total'],
            'source_module' => $invoice['source_module'],
            'source_type' => $invoice['source_type'],
            'source_id' => $invoice['source_id'],
            'source_line_id' => $invoice['source_line_id'],
            'source_reference' => $invoice['source_reference'],
            'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
            'schema_version' => 1,
            'data_json' => $this->json(['scenario' => 'demo']),
            'metadata_json' => $this->json(['seed_source' => 'autoerp_scenario']),
        ]);

        $lineId = $this->idBy('invoice_lines', [
            'tenant_id' => $context['tenant_id'],
            'invoice_id' => $invoiceId,
            'line_no' => 1,
        ]);

        $this->upsert('invoice_source_documents', [
            'tenant_id' => $context['tenant_id'],
            'invoice_id' => $invoiceId,
            'source_module' => $invoice['source_module'],
            'source_type' => $invoice['source_type'],
            'source_id' => $invoice['source_id'],
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'source_reference' => $invoice['source_reference'],
            'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
            'relation_type' => 'source',
            'amount_contributed' => $invoice['total'],
            'metadata_json' => $this->json(['seed_source' => 'autoerp_scenario']),
        ]);

        if ($lineId !== null) {
            $this->upsert('invoice_line_sources', [
                'tenant_id' => $context['tenant_id'],
                'invoice_line_id' => $lineId,
                'source_module' => $invoice['source_module'],
                'source_type' => $invoice['source_type'],
                'source_id' => $invoice['source_id'],
                'source_line_id' => $invoice['source_line_id'],
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'source_reference' => $invoice['source_reference'],
                'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
                'quantity_billed' => $invoice['quantity'],
                'amount_billed' => $invoice['total'],
                'metadata_json' => $this->json(['seed_source' => 'autoerp_scenario']),
            ]);
        }

        $this->upsert('invoice_charges', [
            'tenant_id' => $context['tenant_id'],
            'invoice_id' => $invoiceId,
            'charge_code' => 'HANDLING',
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'charge_name' => 'Handling',
            'charge_type' => 'handling',
            'amount' => $invoice['charge'],
            'tax_group_id' => $context['tax_group_id'],
            'account_id' => $invoice['direction'] === 'payable' ? $context['purchase_account_id'] : $context['sales_account_id'],
            'metadata_json' => $this->json(['seed_source' => 'autoerp_scenario']),
        ]);

        $this->upsert('invoice_discounts', [
            'tenant_id' => $context['tenant_id'],
            'invoice_id' => $invoiceId,
            'discount_code' => 'SEED-DISC',
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'discount_type' => 'fixed',
            'discount_value' => $invoice['discount'],
            'discount_amount' => $invoice['discount'],
            'account_id' => $invoice['direction'] === 'payable' ? $context['purchase_account_id'] : $context['sales_account_id'],
            'metadata_json' => $this->json(['seed_source' => 'autoerp_scenario']),
        ]);

        $this->upsert('invoice_notes', [
            'tenant_id' => $context['tenant_id'],
            'invoice_id' => $invoiceId,
            'note_type' => 'internal',
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'body' => 'Seeded invoice covering status, source, note, charge, discount, allocation, and finance tests.',
            'created_by' => $context['user_id'],
        ]);

        $this->invoiceStatus($context, $invoiceId, null, 'draft', 'Invoice created from seed source.');
        if ($invoice['status'] !== 'draft') {
            $this->invoiceStatus($context, $invoiceId, 'draft', $invoice['status'], 'Seeded invoice lifecycle transition.');
        }

        return $invoiceId;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function seedPaymentForInvoice(array $context, ?int $invoiceId, string $paymentNumber, string $direction, string $amount): void
    {
        if ($invoiceId === null || ! Schema::hasTable('payments') || ! Schema::hasTable('payment_methods')) {
            return;
        }

        $tenantId = (int) $context['tenant_id'];
        $methodId = $this->idBy('payment_methods', ['tenant_id' => $tenantId, 'code' => $direction === 'inbound' ? 'CASH' : 'BANK_TRANSFER'])
            ?? DB::table('payment_methods')->where('tenant_id', $tenantId)->orderBy('id')->value('id');
        if ($methodId === null) {
            return;
        }

        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if ($invoice === null) {
            return;
        }

        $this->upsert('payments', [
            'tenant_id' => $tenantId,
            'payment_number' => $paymentNumber,
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'metadata' => $this->seedMetadata('autoerp_scenario', 'payment_allocation'),
            'party_type' => $invoice->party_type,
            'party_id' => $invoice->party_id,
            'party_role' => $direction === 'inbound' ? 'payer' : 'payee',
            'payer_type' => $direction === 'inbound' ? $invoice->party_type : 'internal_company',
            'payer_id' => $direction === 'inbound' ? $invoice->party_id : null,
            'payer_name' => $direction === 'inbound' ? null : 'AutoERP Demo Company',
            'payee_type' => $direction === 'outbound' ? $invoice->party_type : 'internal_company',
            'payee_id' => $direction === 'outbound' ? $invoice->party_id : null,
            'payee_name' => $direction === 'outbound' ? null : 'AutoERP Demo Company',
            'source_module' => 'invoice',
            'source_type' => 'invoice',
            'source_id' => $invoiceId,
            'source_reference' => $invoice->invoice_number,
            'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
            'reference' => 'Seed payment for '.$invoice->invoice_number,
            'payment_date' => $direction === 'inbound' ? '2026-03-20' : '2026-03-21',
            'amount' => $amount,
            'allocated_amount' => $amount,
            'direction' => $direction,
            'payment_group_id' => null,
            'payment_method_id' => $methodId,
            'account_id' => $context['cash_account_id'],
            'currency_id' => $context['currency_id'],
            'exchange_rate' => '1.0000',
            'base_amount' => $amount,
            'status' => 'fully_allocated',
            'notes' => 'Seeded allocated payment.',
            'idempotency_key' => $paymentNumber.'-IDEMPOTENCY',
            'created_by' => $context['user_id'],
            'posted_by' => $context['user_id'],
            'posted_at' => $direction === 'inbound' ? '2026-03-20 10:00:00' : '2026-03-21 10:00:00',
        ]);

        $paymentId = $this->idBy('payments', ['tenant_id' => $tenantId, 'payment_number' => $paymentNumber]);
        if ($paymentId === null) {
            return;
        }

        $this->upsert('payment_allocations', [
            'tenant_id' => $tenantId,
            'payment_id' => $paymentId,
            'document_type' => 'invoice',
            'document_id' => $invoiceId,
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'metadata' => $this->seedMetadata('autoerp_scenario', 'payment_allocation'),
            'document_line_id' => null,
            'source_module' => 'invoice',
            'source_type' => 'invoice',
            'source_id' => $invoiceId,
            'source_reference' => $invoice->invoice_number,
            'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
            'reference' => $paymentNumber.'-'.$invoice->invoice_number,
            'allocated_amount' => $amount,
            'currency_id' => $context['currency_id'],
            'base_allocated_amount' => $amount,
            'allocation_date' => $direction === 'inbound' ? '2026-03-20' : '2026-03-21',
            'status' => 'active',
        ]);

        $allocationId = DB::table('payment_allocations')
            ->where('tenant_id', $tenantId)
            ->where('payment_id', $paymentId)
            ->where('document_type', 'invoice')
            ->where('document_id', $invoiceId)
            ->value('id');

        $this->upsert('invoice_allocations', [
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'allocation_id' => $allocationId,
            'allocated_amount' => $amount,
            'allocated_at' => $direction === 'inbound' ? '2026-03-20 10:00:00' : '2026-03-21 10:00:00',
            'status' => 'active',
            'metadata_json' => $this->json(['seed_source' => 'autoerp_scenario']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function financeTransaction(
        string $table,
        array $context,
        ?int $invoiceId,
        string $partyType,
        string $transactionType,
        string $debit,
        string $credit,
        string $original,
        string $paid,
        string $outstanding,
        string $status,
        string $reference,
    ): void {
        if ($invoiceId === null) {
            return;
        }

        $accountId = $table === 'ap_transactions' ? $context['payable_account_id'] : $context['receivable_account_id'];
        if ($accountId === null) {
            return;
        }

        $partyId = $partyType === 'supplier' ? $context['supplier_id'] : $context['customer_id'];
        $this->upsert($table, [
            'tenant_id' => $context['tenant_id'],
            'source_module' => 'invoice',
            'source_type' => 'invoice',
            'source_id' => $invoiceId,
            'transaction_type' => $transactionType,
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'metadata' => $this->seedMetadata('autoerp_scenario', $table),
            'party_type' => $partyType,
            'party_id' => $partyId,
            'account_id' => $accountId,
            'reference_type' => 'invoice',
            'reference_id' => $invoiceId,
            'source_reference' => $reference,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'original_amount' => $original,
            'paid_amount' => $paid,
            'outstanding_amount' => $outstanding,
            'balance_after' => $outstanding,
            'transaction_date' => '2026-03-13',
            'due_date' => $reference === 'SI-DEMO-OVERDUE' ? '2026-02-14' : '2026-04-12',
            'status' => $status,
            'currency_id' => $context['currency_id'],
            'exchange_rate' => '1.0000',
            'is_reconciled' => false,
            'created_by' => $context['user_id'],
            'updated_by' => $context['user_id'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function invoiceStatus(array $context, ?int $invoiceId, ?string $from, string $to, string $reason): void
    {
        if ($invoiceId === null) {
            return;
        }

        $this->upsert('invoice_status_histories', [
            'tenant_id' => $context['tenant_id'],
            'invoice_id' => $invoiceId,
            'to_status' => $to,
            'reason' => $reason,
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'from_status' => $from,
            'changed_by' => $context['user_id'],
            'changed_at' => match ($to) {
                'cancelled' => '2026-02-20 12:00:00',
                'posted', 'partially_paid' => '2026-03-13 12:00:00',
                default => '2026-03-13 09:00:00',
            },
            'metadata_json' => $this->json(['seed_source' => 'autoerp_scenario']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function statusHistory(
        string $table,
        array $context,
        string $entityType,
        ?int $entityId,
        ?string $from,
        string $to,
        string $reason,
    ): void {
        if ($entityId === null) {
            return;
        }

        $this->upsert($table, [
            'tenant_id' => $context['tenant_id'],
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'to_status' => $to,
            'reason' => $reason,
        ], [
            'organization_unit_id' => $context['organization_unit_id'],
            'metadata' => $this->seedMetadata('autoerp_scenario', 'status_history'),
            'from_status' => $from,
            'changed_by' => $context['user_id'],
            'changed_at' => '2026-03-13 12:00:00',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function seedVehiclePartyLinks(array $context): void
    {
        $vehicleId = $this->idBy('vehicles', ['tenant_id' => $context['tenant_id'], 'vehicle_code' => 'VEH-DEMO-001']);
        if ($vehicleId !== null) {
            $ownershipId = $this->idBy('vehicle_ownerships', [
                'tenant_id' => $context['tenant_id'],
                'vehicle_id' => $vehicleId,
                'ownership_role' => 'legal_owner',
            ]);

            $this->upsert('customer_vehicles', [
                'tenant_id' => $context['tenant_id'],
                'customer_id' => $context['customer_id'],
                'vehicle_id' => $vehicleId,
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'customer_vehicle'),
                'source_context' => $this->json(['relationship' => 'fleet_service_customer']),
                'relationship_type' => 'serviced_vehicle',
                'vehicle_ownership_id' => $ownershipId,
                'is_current' => true,
                'is_active' => true,
            ]);
        }

        $providerVehicleId = $this->idBy('vehicles', ['tenant_id' => $context['tenant_id'], 'vehicle_code' => 'VEH-DEMO-002']);
        if ($providerVehicleId !== null) {
            $ownershipId = $this->idBy('vehicle_ownerships', [
                'tenant_id' => $context['tenant_id'],
                'vehicle_id' => $providerVehicleId,
                'ownership_role' => 'provider',
            ]);

            $this->upsert('supplier_vehicles', [
                'tenant_id' => $context['tenant_id'],
                'supplier_id' => $context['supplier_id'],
                'vehicle_id' => $providerVehicleId,
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'supplier_vehicle'),
                'source_context' => $this->json(['relationship' => 'rental_provider_vehicle']),
                'relationship_type' => 'provider_vehicle',
                'vehicle_ownership_id' => $ownershipId,
                'is_current' => true,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, int|null>  $purchase
     * @param  array<string, int|null>  $sales
     */
    private function seedExtensionAndAuditEvidence(array $context, array $purchase, array $sales): void
    {
        $invoiceId = $sales['invoice_id'] ?? null;
        if ($invoiceId !== null) {
            $this->upsert('attachments', [
                'tenant_id' => $context['tenant_id'],
                'attachable_type' => 'invoice',
                'attachable_id' => $invoiceId,
                'file_name' => 'signed-delivery-note.pdf',
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'attachment'),
                'source_module' => 'invoice',
                'source_type' => 'invoice',
                'source_id' => $invoiceId,
                'source_reference' => 'SI-DEMO-0001',
                'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
                'file_path' => 'seed/demo/signed-delivery-note.pdf',
                'mime_type' => 'application/pdf',
                'size' => 20480,
            ]);

            $this->upsert('comments', [
                'tenant_id' => $context['tenant_id'],
                'commentable_type' => 'invoice',
                'commentable_id' => $invoiceId,
                'body' => 'Seeded follow-up comment for partially paid invoice.',
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'comment'),
                'source_module' => 'invoice',
                'source_type' => 'invoice',
                'source_id' => $invoiceId,
                'source_reference' => 'SI-DEMO-0001',
                'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
                'author_id' => $context['user_id'],
            ]);

            $this->upsert('entity_attributes', [
                'tenant_id' => $context['tenant_id'],
                'entity_type' => 'invoice',
                'entity_id' => $invoiceId,
                'attribute_key' => 'demo_risk_bucket',
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'entity_attribute'),
                'attribute_value' => 'partial_payment_monitor',
            ]);
        }

        foreach ([
            ['purchase_order', $purchase['po_id'] ?? null, 'PO-DEMO-0001', 'purchase'],
            ['sales_order', $sales['so_id'] ?? null, 'SO-DEMO-0001', 'sales'],
            ['invoice', $invoiceId, 'SI-DEMO-0001', 'invoice'],
        ] as [$type, $id, $reference, $module]) {
            if ($id === null) {
                continue;
            }

            $this->upsert('audit_logs', [
                'tenant_id' => $context['tenant_id'],
                'auditable_type' => $type,
                'auditable_id' => (string) $id,
                'event' => 'seeded',
                'source_reference' => $reference,
            ], [
                'organization_unit_id' => $context['organization_unit_id'],
                'metadata' => $this->seedMetadata('autoerp_scenario', 'audit'),
                'user_id' => $context['user_id'],
                'source_module' => $module,
                'source_type' => $type,
                'source_id' => (string) $id,
                'source_context' => $this->json(['seed_source' => 'autoerp_scenario']),
                'old_values' => null,
                'new_values' => $this->json(['reference' => $reference, 'status' => 'seeded']),
                'url' => '/seed/demo',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'AutoERP Database Seeder',
                'tags' => $this->json(['seed', 'demo', $module]),
                'occurred_at' => '2026-03-13 12:30:00',
            ]);
        }
    }
}
