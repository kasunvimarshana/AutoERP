<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PurchaseSampleSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->hasTables(['purchase_orders', 'purchase_order_lines', 'grn_headers', 'grn_lines', 'purchase_settings'])) {
            return;
        }

        DB::transaction(function (): void {
            $tenantId = $this->tenantId();
            if ($tenantId < 1) {
                return;
            }

            $organizationUnitId = $this->organizationUnitId($tenantId);
            $supplierId = $this->id('suppliers', ['tenant_id' => $tenantId, 'supplier_code' => 'SUP-DEMO-002'])
                ?? $this->firstId('suppliers', ['tenant_id' => $tenantId]);
            $warehouseId = $this->id('warehouses', ['tenant_id' => $tenantId, 'code' => 'MAIN'])
                ?? $this->firstId('warehouses', ['tenant_id' => $tenantId]);
            $currencyId = $this->id('currencies', ['code' => 'LKR']) ?? $this->firstId('currencies');
            $filterItemId = $this->id('items', ['tenant_id' => $tenantId, 'sku' => 'ITM-FILTER-001']);
            $supplyItemId = $this->id('items', ['tenant_id' => $tenantId, 'sku' => 'ITM-SHOPSUPPLY-001']);

            if ($supplierId === null || $warehouseId === null || $filterItemId === null || $supplyItemId === null) {
                return;
            }

            $filterUomId = $this->purchaseUomId($tenantId, $filterItemId);
            $supplyUomId = $this->purchaseUomId($tenantId, $supplyItemId);
            if ($filterUomId === null || $supplyUomId === null) {
                return;
            }

            $this->settings($tenantId, $organizationUnitId, $currencyId, $warehouseId);

            $poId = $this->purchaseOrder($tenantId, $organizationUnitId, $supplierId, $warehouseId, $currencyId);
            $filterLineId = $this->purchaseOrderLine($tenantId, $organizationUnitId, $poId, $filterItemId, $filterUomId, 'PO-SAMPLE-0001-L1', 2, 1200);
            $supplyLineId = $this->purchaseOrderLine($tenantId, $organizationUnitId, $poId, $supplyItemId, $supplyUomId, 'PO-SAMPLE-0001-L2', 5, 150);
            $this->updatePurchaseOrderTotals($poId);

            $grnId = $this->grn($tenantId, $organizationUnitId, $supplierId, $warehouseId, $currencyId, $poId);
            $filterGrnLineId = $this->grnLine($tenantId, $organizationUnitId, $grnId, $filterLineId, $filterItemId, $filterUomId, 'GRN-SAMPLE-0001-L1', 1, 1200);
            $this->grnLine($tenantId, $organizationUnitId, $grnId, $supplyLineId, $supplyItemId, $supplyUomId, 'GRN-SAMPLE-0001-L2', 5, 150);
            $this->updateGrnTotals($grnId);
            DB::table('purchase_order_lines')->where('id', $filterLineId)->update(['received_qty' => 1, 'updated_at' => now()]);
            DB::table('purchase_order_lines')->where('id', $supplyLineId)->update(['received_qty' => 5, 'updated_at' => now()]);

            $returnId = $this->purchaseReturn($tenantId, $organizationUnitId, $supplierId, $currencyId, $poId, $grnId);
            $this->purchaseReturnLine($tenantId, $organizationUnitId, $returnId, $filterGrnLineId, $filterLineId, $filterItemId, $filterUomId);
            $this->updateReturnTotals($returnId);

            $paymentId = $this->supplierPayment($tenantId, $organizationUnitId, $supplierId, $currencyId, $poId);
            $this->paymentAllocation($tenantId, $organizationUnitId, $paymentId, $poId, $currencyId);
            $this->supplierAdvance($tenantId, $organizationUnitId, $supplierId, $currencyId, $paymentId);
            $this->supplierRefund($tenantId, $organizationUnitId, $supplierId, $currencyId, $returnId);
        });
    }

    /**
     * @param list<string> $tables
     */
    private function hasTables(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function settings(int $tenantId, ?int $organizationUnitId, ?int $currencyId, int $warehouseId): void
    {
        $definitionId = Schema::hasTable('document_definitions')
            ? $this->firstId('document_definitions', ['tenant_id' => $tenantId])
            : null;

        DB::table('purchase_settings')->updateOrInsert(
            ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId],
            [
                'allow_direct_grn' => true,
                'allow_direct_purchase_document' => true,
                'allow_header_discount' => true,
                'allow_line_discount' => true,
                'allow_negative_stock_on_return' => false,
                'allow_return_without_original' => true,
                'default_currency_id' => $currencyId,
                'default_document_status' => 'draft',
                'default_grn_status' => 'draft',
                'default_po_status' => 'draft',
                'default_return_status' => 'draft',
                'default_supplier_payable_account_id' => $this->accountId($tenantId, '2000'),
                'default_purchase_account_id' => $this->accountId($tenantId, '5000'),
                'default_inventory_account_id' => $this->accountId($tenantId, '1000'),
                'default_purchase_tax_account_id' => $this->accountId($tenantId, '2100'),
                'default_warehouse_id' => $warehouseId,
                'grn_document_definition_id' => $definitionId,
                'header_discount_allocation_method' => 'proportional',
                'is_active' => true,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'numbering_sequence_code' => 'PO-',
                'purchase_invoice_document_definition_id' => $definitionId,
                'purchase_order_document_definition_id' => $definitionId,
                'purchase_return_document_definition_id' => $definitionId,
                'require_grn_before_invoice' => false,
                'require_po_before_grn' => false,
                'row_version' => 1,
                'tax_calculation_level' => 'line',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function purchaseOrder(int $tenantId, ?int $organizationUnitId, int $supplierId, int $warehouseId, ?int $currencyId): int
    {
        DB::table('purchase_orders')->updateOrInsert(
            ['tenant_id' => $tenantId, 'po_number' => 'PO-SAMPLE-0001'],
            [
                'balance' => 3150,
                'currency_id' => $currencyId,
                'document_status' => 'not_documented',
                'exchange_rate' => 1,
                'expected_date' => now()->addDays(7)->toDateString(),
                'grand_total' => 3150,
                'line_discount_total' => 0,
                'line_tax_total' => 0,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'notes' => 'Seeded multi-line purchase order for Purchase UI verification.',
                'order_date' => now()->toDateString(),
                'organization_unit_id' => $organizationUnitId,
                'paid_amount' => 0,
                'reference' => 'PUR-SEED-PO',
                'row_version' => 1,
                'status' => 'approved',
                'subtotal' => 3150,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('purchase_orders')->where('tenant_id', $tenantId)->where('po_number', 'PO-SAMPLE-0001')->value('id');
    }

    private function purchaseOrderLine(int $tenantId, ?int $organizationUnitId, int $poId, int $itemId, int $uomId, string $reference, float $qty, float $unitPrice): int
    {
        $gross = $qty * $unitPrice;
        DB::table('purchase_order_lines')->updateOrInsert(
            ['tenant_id' => $tenantId, 'purchase_order_id' => $poId, 'reference' => $reference],
            [
                'discount_amount' => 0,
                'discount_value' => 0,
                'gross_amount' => $gross,
                'item_id' => $itemId,
                'line_total' => $gross,
                'line_total_with_tax' => $gross,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'ordered_qty' => $qty,
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'tax_amount' => 0,
                'unit_price' => $unitPrice,
                'uom_id' => $uomId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('purchase_order_lines')->where('tenant_id', $tenantId)->where('purchase_order_id', $poId)->where('reference', $reference)->value('id');
    }

    private function grn(int $tenantId, ?int $organizationUnitId, int $supplierId, int $warehouseId, ?int $currencyId, int $poId): int
    {
        DB::table('grn_headers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'grn_number' => 'GRN-SAMPLE-0001'],
            [
                'currency_id' => $currencyId,
                'document_status' => 'not_documented',
                'exchange_rate' => 1,
                'grand_total' => 1950,
                'inspection_status' => 'accepted',
                'line_discount_total' => 0,
                'line_tax_total' => 0,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'notes' => 'Seeded GRN linked to PO-SAMPLE-0001.',
                'organization_unit_id' => $organizationUnitId,
                'purchase_order_id' => $poId,
                'putaway_status' => 'pending',
                'received_date' => now()->toDateString(),
                'reference' => 'PUR-SEED-GRN',
                'row_version' => 1,
                'status' => 'confirmed',
                'subtotal' => 1950,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('grn_headers')->where('tenant_id', $tenantId)->where('grn_number', 'GRN-SAMPLE-0001')->value('id');
    }

    private function grnLine(int $tenantId, ?int $organizationUnitId, int $grnId, int $poLineId, int $itemId, int $uomId, string $reference, float $qty, float $unitPrice): int
    {
        $gross = $qty * $unitPrice;
        DB::table('grn_lines')->updateOrInsert(
            ['tenant_id' => $tenantId, 'grn_header_id' => $grnId, 'reference' => $reference],
            [
                'accepted_qty' => $qty,
                'discount_amount' => 0,
                'discount_value' => 0,
                'expected_qty' => $qty,
                'gross_amount' => $gross,
                'inspected_qty' => $qty,
                'item_id' => $itemId,
                'line_total' => $gross,
                'line_total_with_tax' => $gross,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'organization_unit_id' => $organizationUnitId,
                'purchase_order_line_id' => $poLineId,
                'received_qty' => $qty,
                'row_version' => 1,
                'tax_amount' => 0,
                'unit_price' => $unitPrice,
                'uom_id' => $uomId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('grn_lines')->where('tenant_id', $tenantId)->where('grn_header_id', $grnId)->where('reference', $reference)->value('id');
    }

    private function purchaseReturn(int $tenantId, ?int $organizationUnitId, int $supplierId, ?int $currencyId, int $poId, int $grnId): int
    {
        DB::table('purchase_returns')->updateOrInsert(
            ['tenant_id' => $tenantId, 'return_number' => 'PRET-SAMPLE-0001'],
            [
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'grand_total' => 300,
                'is_without_original' => false,
                'line_discount_total' => 0,
                'line_restocking_total' => 0,
                'line_tax_total' => 0,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'notes' => 'Seeded supplier return for one partially received item.',
                'organization_unit_id' => $organizationUnitId,
                'original_grn_id' => $grnId,
                'original_purchase_order_id' => $poId,
                'reference' => 'PUR-SEED-RETURN',
                'return_date' => now()->toDateString(),
                'return_reason' => 'Damaged packaging',
                'row_version' => 1,
                'status' => 'approved',
                'subtotal' => 300,
                'supplier_id' => $supplierId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('purchase_returns')->where('tenant_id', $tenantId)->where('return_number', 'PRET-SAMPLE-0001')->value('id');
    }

    private function purchaseReturnLine(int $tenantId, ?int $organizationUnitId, int $returnId, int $grnLineId, int $poLineId, int $itemId, int $uomId): void
    {
        DB::table('purchase_return_lines')->updateOrInsert(
            ['tenant_id' => $tenantId, 'purchase_return_id' => $returnId, 'reference' => 'PRET-SAMPLE-0001-L1'],
            [
                'condition' => 'damaged',
                'discount_amount' => 0,
                'discount_value' => 0,
                'disposition' => 'return_to_vendor',
                'gross_amount' => 300,
                'item_id' => $itemId,
                'line_total' => 300,
                'line_total_with_tax' => 300,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'organization_unit_id' => $organizationUnitId,
                'original_grn_line_id' => $grnLineId,
                'original_purchase_order_line_id' => $poLineId,
                'quality_check_notes' => 'Seeded damaged packaging return.',
                'return_qty' => 0.25,
                'row_version' => 1,
                'tax_amount' => 0,
                'unit_price' => 1200,
                'uom_id' => $uomId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function supplierPayment(int $tenantId, ?int $organizationUnitId, int $supplierId, ?int $currencyId, int $poId): ?int
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('payment_methods')) {
            return null;
        }

        $methodId = $this->id('payment_methods', ['tenant_id' => $tenantId, 'code' => 'BANK_TRANSFER'])
            ?? $this->firstId('payment_methods', ['tenant_id' => $tenantId]);
        if ($methodId === null) {
            return null;
        }

        DB::table('payments')->updateOrInsert(
            ['tenant_id' => $tenantId, 'payment_number' => 'SPAY-SAMPLE-0001'],
            [
                'account_id' => $this->accountId($tenantId, '1010'),
                'allocated_amount' => 1000,
                'amount' => 1000,
                'base_amount' => 1000,
                'currency_id' => $currencyId,
                'direction' => 'outbound',
                'exchange_rate' => 1,
                'metadata' => json_encode(['seed_source' => 'purchase_sample', 'supplier_label' => 'Auto Parts Lanka']),
                'organization_unit_id' => $organizationUnitId,
                'party_id' => $supplierId,
                'party_role' => 'supplier',
                'party_type' => 'supplier',
                'payee_id' => $supplierId,
                'payee_name' => 'Auto Parts Lanka',
                'payee_type' => 'supplier',
                'payment_date' => now()->toDateString(),
                'payment_method_id' => $methodId,
                'reference' => 'PO-SAMPLE-0001 advance settlement',
                'row_version' => 1,
                'source_id' => $poId,
                'source_module' => 'purchase',
                'source_reference' => 'PO-SAMPLE-0001',
                'source_type' => 'purchase_order',
                'status' => 'posted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('payments')->where('tenant_id', $tenantId)->where('payment_number', 'SPAY-SAMPLE-0001')->value('id');
    }

    private function paymentAllocation(int $tenantId, ?int $organizationUnitId, ?int $paymentId, int $poId, ?int $currencyId): void
    {
        if ($paymentId === null || ! Schema::hasTable('payment_allocations')) {
            return;
        }

        DB::table('payment_allocations')->updateOrInsert(
            ['tenant_id' => $tenantId, 'payment_id' => $paymentId, 'document_type' => 'purchase_order', 'document_id' => $poId],
            [
                'allocated_amount' => 1000,
                'allocation_date' => now()->toDateString(),
                'base_allocated_amount' => 1000,
                'currency_id' => $currencyId,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'organization_unit_id' => $organizationUnitId,
                'reference' => 'PO-SAMPLE-0001',
                'row_version' => 1,
                'source_id' => $poId,
                'source_module' => 'purchase',
                'source_reference' => 'PO-SAMPLE-0001',
                'source_type' => 'purchase_order',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function supplierAdvance(int $tenantId, ?int $organizationUnitId, int $supplierId, ?int $currencyId, ?int $paymentId): void
    {
        if (! Schema::hasTable('advance_payments')) {
            return;
        }

        DB::table('advance_payments')->updateOrInsert(
            ['tenant_id' => $tenantId, 'advance_number' => 'SADV-SAMPLE-0001'],
            [
                'advance_date' => now()->subDays(2)->toDateString(),
                'amount' => 500,
                'base_amount' => 500,
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'metadata' => json_encode(['seed_source' => 'purchase_sample']),
                'notes' => 'Seeded supplier advance for Purchase UI verification.',
                'organization_unit_id' => $organizationUnitId,
                'party_id' => $supplierId,
                'party_type' => 'supplier',
                'payment_id' => $paymentId,
                'reference' => 'SADV-SEED',
                'remaining_amount' => 500,
                'row_version' => 1,
                'status' => 'open',
                'type' => 'supplier',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function supplierRefund(int $tenantId, ?int $organizationUnitId, int $supplierId, ?int $currencyId, int $returnId): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('payment_methods')) {
            return;
        }

        $methodId = $this->id('payment_methods', ['tenant_id' => $tenantId, 'code' => 'BANK_TRANSFER'])
            ?? $this->firstId('payment_methods', ['tenant_id' => $tenantId]);
        if ($methodId === null) {
            return;
        }

        DB::table('payments')->updateOrInsert(
            ['tenant_id' => $tenantId, 'payment_number' => 'REF-PUR-SAMPLE-0001'],
            [
                'account_id' => $this->accountId($tenantId, '1010'),
                'allocated_amount' => 300,
                'amount' => 300,
                'base_amount' => 300,
                'currency_id' => $currencyId,
                'direction' => 'inbound',
                'exchange_rate' => 1,
                'metadata' => json_encode(['seed_source' => 'purchase_sample', 'supplier_label' => 'Auto Parts Lanka']),
                'organization_unit_id' => $organizationUnitId,
                'party_id' => $supplierId,
                'party_role' => 'supplier_refund',
                'party_type' => 'supplier',
                'payer_id' => $supplierId,
                'payer_name' => 'Auto Parts Lanka',
                'payer_type' => 'supplier',
                'payment_date' => now()->toDateString(),
                'payment_method_id' => $methodId,
                'reference' => 'Refund for PRET-SAMPLE-0001',
                'row_version' => 1,
                'source_id' => $returnId,
                'source_module' => 'purchase',
                'source_reference' => 'PRET-SAMPLE-0001',
                'source_type' => 'purchase_return',
                'status' => 'posted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function updatePurchaseOrderTotals(int $poId): void
    {
        $subtotal = (float) DB::table('purchase_order_lines')->where('purchase_order_id', $poId)->sum('line_total');
        DB::table('purchase_orders')->where('id', $poId)->update([
            'balance' => $subtotal,
            'grand_total' => $subtotal,
            'subtotal' => $subtotal,
            'updated_at' => now(),
        ]);
    }

    private function updateGrnTotals(int $grnId): void
    {
        $subtotal = (float) DB::table('grn_lines')->where('grn_header_id', $grnId)->sum('line_total');
        DB::table('grn_headers')->where('id', $grnId)->update([
            'grand_total' => $subtotal,
            'subtotal' => $subtotal,
            'updated_at' => now(),
        ]);
    }

    private function updateReturnTotals(int $returnId): void
    {
        $subtotal = (float) DB::table('purchase_return_lines')->where('purchase_return_id', $returnId)->sum('line_total');
        DB::table('purchase_returns')->where('id', $returnId)->update([
            'grand_total' => $subtotal,
            'subtotal' => $subtotal,
            'updated_at' => now(),
        ]);
    }

    private function tenantId(): int
    {
        $id = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');

        return $id > 0 ? $id : (int) DB::table('tenants')->where('is_active', true)->orderBy('id')->value('id');
    }

    private function organizationUnitId(int $tenantId): ?int
    {
        $id = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        return $id > 0 ? $id : $this->firstId('organization_units', ['tenant_id' => $tenantId]);
    }

    private function purchaseUomId(int $tenantId, int $itemId): ?int
    {
        $item = DB::table('items')->where('tenant_id', $tenantId)->where('id', $itemId)->first([
            'base_uom_id',
            'default_receipt_uom_id',
        ]);

        if ($item === null) {
            return null;
        }

        $id = (int) ($item->default_receipt_uom_id ?? 0);

        return $id > 0 ? $id : ((int) ($item->base_uom_id ?? 0) ?: null);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function id(string $table, array $criteria): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);
        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        $id = (int) $query->value('id');

        return $id > 0 ? $id : null;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function firstId(string $table, array $criteria = []): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);
        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        $id = (int) $query->orderBy('id')->value('id');

        return $id > 0 ? $id : null;
    }

    private function accountId(int $tenantId, string $code): ?int
    {
        return $this->id('accounts', ['tenant_id' => $tenantId, 'code' => $code]);
    }
}
