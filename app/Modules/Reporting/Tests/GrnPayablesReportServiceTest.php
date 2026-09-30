<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Enums\PurchaseReturnType;
use Modules\Reporting\Services\GrnPayablesReportService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class GrnPayablesReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reconciles_uninvoiced_grns_shared_invoice_balances_return_credits_and_grni(): void
    {
        $tenantId = $this->tenant();
        $warehouseId = $this->warehouse($tenantId);
        $supplierId = $this->supplier($tenantId);
        $itemId = $this->item($tenantId);
        FinancePostingFixture::seedPurchasePostingProfiles($tenantId);

        [$partialGrnId, $partialLineId] = $this->grn($tenantId, $warehouseId, $supplierId, $itemId, 'GRN-001', '1000', '10', '5');
        [$openGrnId] = $this->grn($tenantId, $warehouseId, $supplierId, $itemId, 'GRN-002', '500', '5', '0');
        [$invoicedGrnId, $invoicedLineId] = $this->grn($tenantId, $warehouseId, $supplierId, $itemId, 'GRN-003', '300', '5', '5');

        $sharedInvoiceId = $this->invoice($tenantId, $supplierId, 'PINV-001', '700', '175', InvoiceStatus::PartiallyPaid);
        $this->invoiceLink($tenantId, $sharedInvoiceId, $partialGrnId, '400');
        $this->invoiceLink($tenantId, $sharedInvoiceId, $invoicedGrnId, '300');

        $draftInvoiceId = $this->invoice($tenantId, $supplierId, 'PINV-DRAFT', '100', '100', InvoiceStatus::Draft);
        $this->invoiceLink($tenantId, $draftInvoiceId, $partialGrnId, '100');

        $this->returnCredit($tenantId, $warehouseId, $supplierId, $partialGrnId, '50');

        $grniAccountId = $this->grniAccount($tenantId);
        $this->ledger($tenantId, $grniAccountId, 'GRN-001-R', 'goods_receipt_note', $partialGrnId, 'goods_receipt_note', $partialGrnId, '0', '900');
        $this->ledger($tenantId, $grniAccountId, 'GRN-001-I', 'invoice', $sharedInvoiceId, 'goods_receipt_note_line', $partialLineId, '360', '0');
        $this->ledger($tenantId, $grniAccountId, 'GRN-002-R', 'goods_receipt_note', $openGrnId, 'goods_receipt_note', $openGrnId, '0', '500');
        $this->ledger($tenantId, $grniAccountId, 'GRN-003-R', 'goods_receipt_note', $invoicedGrnId, 'goods_receipt_note', $invoicedGrnId, '0', '300');
        $this->ledger($tenantId, $grniAccountId, 'GRN-003-I', 'invoice', $sharedInvoiceId, 'goods_receipt_note_line', $invoicedLineId, '300', '0');

        $result = app(GrnPayablesReportService::class)->run([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'page' => 1,
            'per_page' => 25,
        ]);

        self::assertSame(3, $result['summary']['grn_count']);
        self::assertSame(1, $result['summary']['not_invoiced_count']);
        self::assertSame(1, $result['summary']['partially_invoiced_count']);
        self::assertSame(1, $result['summary']['invoiced_count']);
        self::assertSame(3, $result['summary']['open_exposure_count']);
        self::assertSame(1, $result['summary']['open_return_credit_count']);
        self::assertSame('500.000000', $result['summary']['not_invoiced_amount']);
        self::assertSame('500.000000', $result['summary']['partially_invoiced_amount']);
        self::assertSame('75.000000', $result['summary']['invoiced_ap_outstanding']);
        self::assertSame('1800.000000', $result['summary']['receipt_total']);
        self::assertSame('800.000000', $result['summary']['linked_invoice_amount']);
        self::assertSame('700.000000', $result['summary']['finalized_invoice_amount']);
        self::assertSame('100.000000', $result['summary']['pending_invoice_amount']);
        self::assertSame('1000.000000', $result['summary']['uninvoiced_amount']);
        self::assertSame('525.000000', $result['summary']['settled_invoice_amount']);
        self::assertSame('175.000000', $result['summary']['ap_outstanding']);
        self::assertSame('50.000000', $result['summary']['open_return_credit']);
        self::assertSame('1125.000000', $result['summary']['projected_exposure']);
        self::assertSame('1040.000000', $result['summary']['grni_balance']);
        self::assertSame('1215.000000', $result['summary']['accounting_liability']);

        $rows = collect($result['data'])->keyBy('grn_number');
        self::assertSame('100.000000', $rows['GRN-001']['ap_outstanding']);
        self::assertSame('75.000000', $rows['GRN-003']['ap_outstanding']);
        self::assertSame('550.000000', $rows['GRN-001']['projected_exposure']);
        self::assertSame('540.000000', $rows['GRN-001']['grni_balance']);
        self::assertSame('partially_invoiced', $rows['GRN-001']['invoice_progress']);
        self::assertSame('open', $rows['GRN-003']['exposure_status']);

        self::assertCount(1, $result['suppliers']);
        self::assertSame('1125.000000', $result['suppliers'][0]['projected_exposure']);
    }

    private function tenant(): int
    {
        $suffix = Str::lower(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-GRN-'.strtoupper($suffix),
            'name' => 'GRN Payables '.$suffix,
            'slug' => 'grn-payables-'.$suffix,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function warehouse(int $tenantId): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function supplier(int $tenantId): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'supplier_number' => 'SUP-001',
            'code' => 'SUP-001',
            'name' => 'Parts Supplier',
            'display_name' => 'Parts Supplier',
            'supplier_type' => 'local',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function item(int $tenantId): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'code' => 'ITEM-001',
            'name' => 'Stock Item',
            'item_type' => 'stock',
            'is_stockable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array{int, int} */
    private function grn(
        int $tenantId,
        int $warehouseId,
        int $supplierId,
        int $itemId,
        string $number,
        string $total,
        string $accepted,
        string $invoiced,
    ): array {
        $grnId = (int) DB::table('goods_receipt_notes')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'supplier_type' => 'supplier',
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'grn_number' => $number,
            'received_date' => '2026-08-15',
            'status' => GoodsReceiptNoteStatus::Posted->value,
            'subtotal' => $total,
            'grand_total' => $total,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lineId = (int) DB::table('goods_receipt_note_lines')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'goods_receipt_note_id' => $grnId,
            'item_id' => $itemId,
            'received_quantity' => $accepted,
            'accepted_quantity' => $accepted,
            'invoiced_quantity' => $invoiced,
            'remaining_quantity' => $accepted,
            'unit_price' => '100',
            'line_subtotal' => $total,
            'line_total' => $total,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$grnId, $lineId];
    }

    private function invoice(int $tenantId, int $supplierId, string $number, string $total, string $balance, InvoiceStatus $status): int
    {
        return (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'invoice_number' => $number,
            'invoice_type' => InvoiceType::Purchase->value,
            'direction' => InvoiceDirection::Inbound->value,
            'party_type' => 'supplier',
            'party_id' => $supplierId,
            'party_code_snapshot' => 'SUP-001',
            'party_name_snapshot' => 'Parts Supplier',
            'invoice_date' => '2026-08-20',
            'status' => $status->value,
            'grand_total' => $total,
            'paid_total' => app(DecimalMath::class)->sub($total, $balance),
            'balance_due' => $balance,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoiceLink(int $tenantId, int $invoiceId, int $grnId, string $amount): void
    {
        DB::table('purchase_invoice_links')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'invoice_id' => $invoiceId,
            'source_type' => 'goods_receipt_note',
            'source_id' => $grnId,
            'source_line_total' => $amount,
            'invoice_total' => $amount,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function returnCredit(int $tenantId, int $warehouseId, int $supplierId, int $grnId, string $amount): void
    {
        $returnId = (int) DB::table('purchase_returns')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'supplier_type' => 'supplier',
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'return_number' => 'PRET-001',
            'return_type' => PurchaseReturnType::Referenced->value,
            'source_type' => 'goods_receipt_note',
            'source_id' => $grnId,
            'return_date' => '2026-08-25',
            'status' => PurchaseReturnStatus::Posted->value,
            'subtotal' => $amount,
            'grand_total' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $debitNoteId = (int) DB::table('purchase_debit_notes')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'supplier_type' => 'supplier',
            'supplier_id' => $supplierId,
            'purchase_return_id' => $returnId,
            'source_type' => 'purchase_return',
            'source_id' => $returnId,
            'debit_note_number' => 'PDN-001',
            'debit_note_date' => '2026-08-25',
            'status' => PurchaseDebitNoteStatus::Posted->value,
            'amount' => $amount,
            'remaining_amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('purchase_returns')->where('id', $returnId)->update(['debit_note_id' => $debitNoteId]);
    }

    private function grniAccount(int $tenantId): int
    {
        return (int) DB::table('finance_account_assignments as assignments')
            ->join('finance_account_roles as roles', 'roles.id', '=', 'assignments.account_role_id')
            ->where('assignments.tenant_id', $tenantId)
            ->where('roles.code', FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value)
            ->value('assignments.account_id');
    }

    private function ledger(
        int $tenantId,
        int $accountId,
        string $number,
        string $sourceType,
        int $sourceId,
        string $sourceLineType,
        int $sourceLineId,
        string $debit,
        string $credit,
    ): void {
        $journalId = (int) DB::table('finance_journal_entries')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'journal_number' => $number,
            'journal_date' => '2026-08-20',
            'source_module' => $sourceType === 'invoice' ? 'invoice' : 'purchase',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_number' => $number,
            'source_date' => '2026-08-20',
            'status' => 'posted',
            'total_debit' => $debit,
            'total_credit' => $credit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $journalLineId = (int) DB::table('finance_journal_lines')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'journal_entry_id' => $journalId,
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'source_line_type' => $sourceLineType,
            'source_line_id' => $sourceLineId,
            'line_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('finance_ledger_entries')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'journal_entry_id' => $journalId,
            'journal_line_id' => $journalLineId,
            'account_id' => $accountId,
            'entry_date' => '2026-08-20',
            'debit' => $debit,
            'credit' => $credit,
            'source_module' => $sourceType === 'invoice' ? 'invoice' : 'purchase',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_number' => $number,
            'source_date' => '2026-08-20',
            'source_line_type' => $sourceLineType,
            'source_line_id' => $sourceLineId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
