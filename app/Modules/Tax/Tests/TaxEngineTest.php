<?php

declare(strict_types=1);

namespace Modules\Tax\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesReturn;
use Modules\Tax\DTOs\ApplicableTaxData;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\DTOs\TaxDeterminationContext;
use Modules\Tax\Models\Tax;
use Modules\Tax\Models\TaxDocumentSnapshot;
use Modules\Tax\Models\TaxGroup;
use Modules\Tax\Models\TaxTransaction;
use Modules\Tax\Services\TaxCalculationService;
use Modules\Tax\Services\TaxDeterminationService;
use Modules\Tax\Services\TaxDocumentIntegrationService;
use Modules\Tax\Services\TaxMasterDataService;
use Modules\Tax\Services\TaxPostingContextService;
use Modules\Tax\Services\TaxReportService;
use Modules\Tax\Services\TaxReturnAllocationService;
use Modules\Tax\Services\TaxSnapshotService;
use Tests\TestCase;

final class TaxEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_creation_rates_groups_and_validation_guardrails(): void
    {
        $tenantId = $this->createTenant();
        $service = app(TaxMasterDataService::class);

        $tax = $service->saveTax($this->taxPayload($tenantId, 'GEN-15', 'General Tax', 'VAT', 'exclusive', payable: true));
        $this->assertSame('GEN-15', $tax->code);

        $service->saveRate($tax, [
            'rate' => '15.000000',
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
            'active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax rate effective dates overlap');
        $service->saveRate($tax, [
            'rate' => '16.000000',
            'effective_from' => '2026-06-01',
            'effective_to' => null,
            'active' => true,
        ]);
    }

    public function test_tax_groups_reject_duplicate_sequence_inactive_tax_and_duplicate_code(): void
    {
        $tenantId = $this->createTenant();
        $service = app(TaxMasterDataService::class);
        $tax = $this->createTax($tenantId, 'VAT-10', 'VAT', 'exclusive', '10.000000');

        $group = $service->saveGroup([
            'tenant_id' => $tenantId,
            'code' => 'DEFAULT',
            'name' => 'Default Taxes',
            'is_default' => true,
            'active' => true,
        ], [
            ['tax_id' => $tax->getKey(), 'sequence' => 1, 'active' => true],
        ]);

        $this->assertTrue((bool) $group->is_default);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax group code already exists');
        $service->saveGroup([
            'tenant_id' => $tenantId,
            'code' => 'DEFAULT',
            'name' => 'Duplicate',
            'active' => true,
        ], []);
    }

    public function test_tax_determination_uses_item_profile_document_and_default_priority(): void
    {
        $tenantId = $this->createTenant();
        $itemTax = $this->createTax($tenantId, 'ITEM-TAX', 'ITEM', 'exclusive', '7.000000');
        $profileTax = $this->createTax($tenantId, 'PROFILE-TAX', 'PROFILE', 'exclusive', '8.000000');
        $defaultTax = $this->createTax($tenantId, 'DEFAULT-TAX', 'DEFAULT', 'exclusive', '9.000000');
        $itemGroup = $this->createGroup($tenantId, 'ITEM-GROUP', $itemTax);
        $profileGroup = $this->createGroup($tenantId, 'PROFILE-GROUP', $profileTax);
        $this->createGroup($tenantId, 'DEFAULT-GROUP', $defaultTax, isDefault: true);
        $customerId = $this->createCustomer($tenantId, 'CUST-TAX');
        $itemId = $this->createItem($tenantId, 'ITEM-TAXED', defaultTaxGroupId: (int) $itemGroup->getKey());

        app(TaxMasterDataService::class)->saveCustomerProfile([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'tax_group_id' => $profileGroup->getKey(),
            'exemption_status' => 'taxable',
            'active' => true,
        ]);

        $determine = app(TaxDeterminationService::class);
        $itemResult = $determine->determine(new TaxDeterminationContext(
            tenantId: $tenantId,
            documentType: 'sales_invoice',
            documentDate: '2026-06-11',
            customerId: $customerId,
            itemId: $itemId,
        ));
        $this->assertSame('ITEM-TAX', $itemResult->taxes[0]->taxCode);

        $profileResult = $determine->determine(new TaxDeterminationContext(
            tenantId: $tenantId,
            documentType: 'sales_invoice',
            documentDate: '2026-06-11',
            customerId: $customerId,
        ));
        $this->assertSame('PROFILE-TAX', $profileResult->taxes[0]->taxCode);

        app(TaxMasterDataService::class)->saveCustomerProfile([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'tax_group_id' => $profileGroup->getKey(),
            'exemption_status' => 'zero-rated',
            'active' => true,
        ]);
        $zeroRated = $determine->determine(new TaxDeterminationContext(
            tenantId: $tenantId,
            documentType: 'sales_invoice',
            documentDate: '2026-06-11',
            customerId: $customerId,
        ));
        $this->assertSame('0.000000', $zeroRated->taxes[0]->rate);
    }

    public function test_line_header_inclusive_exclusive_compound_and_wht_calculation(): void
    {
        $calculator = app(TaxCalculationService::class);

        $exclusive = $calculator->calculate(new TaxCalculationData(
            tenantId: 1,
            documentType: 'manual',
            documentDate: '2026-06-11',
            lines: [new TaxCalculationLineData(
                lineNumber: 1,
                taxableAmount: '100.000000',
                applicableTaxes: [new ApplicableTaxData(1, 'OUT', 'Output', 'VAT', 'exclusive', '15.000000', 1, payable: true)],
            )],
        ));
        $this->assertSame('15.000000', $exclusive->taxAmount);
        $this->assertSame('115.000000', $exclusive->totalAmount);

        $inclusive = $calculator->calculate(new TaxCalculationData(
            tenantId: 1,
            documentType: 'manual',
            documentDate: '2026-06-11',
            lines: [new TaxCalculationLineData(
                lineNumber: 1,
                taxableAmount: '115.000000',
                applicableTaxes: [new ApplicableTaxData(2, 'INC', 'Inclusive', 'GST', 'inclusive', '15.000000', 1, payable: true)],
            )],
        ));
        $this->assertSame('15.000000', $inclusive->taxAmount);
        $this->assertSame('115.000000', $inclusive->totalAmount);

        $compound = $calculator->calculate(new TaxCalculationData(
            tenantId: 1,
            documentType: 'manual',
            documentDate: '2026-06-11',
            lines: [new TaxCalculationLineData(
                lineNumber: 1,
                taxableAmount: '100.000000',
                applicableTaxes: [
                    new ApplicableTaxData(3, 'A', 'Tax A', 'CUSTOM', 'exclusive', '10.000000', 1, payable: true),
                    new ApplicableTaxData(4, 'B', 'Tax B', 'CUSTOM', 'compound', '5.000000', 2, payable: true),
                ],
            )],
        ));
        $this->assertSame('15.500000', $compound->taxAmount);
        $this->assertSame('115.500000', $compound->totalAmount);

        $wht = $calculator->calculate(new TaxCalculationData(
            tenantId: 1,
            documentType: 'supplier_invoice',
            documentDate: '2026-06-11',
            lines: [new TaxCalculationLineData(
                lineNumber: 1,
                taxableAmount: '100000.000000',
                applicableTaxes: [new ApplicableTaxData(5, 'WHT', 'Withholding', 'WHT', 'percentage', '5.000000', 1, isWithholding: true, payable: true)],
            )],
        ));
        $this->assertSame('5000.000000', $wht->withholdingAmount);
        $this->assertSame('95000.000000', $wht->totalAmount);
    }

    public function test_header_tax_uses_configured_group_without_line_tax_duplication(): void
    {
        $tenantId = $this->createTenant();
        $tax = $this->createTax($tenantId, 'HDR', 'HEADER', 'exclusive', '10.000000');
        $group = $this->createGroup($tenantId, 'HEADER-GROUP', $tax);

        $result = app(TaxCalculationService::class)->calculate(new TaxCalculationData(
            tenantId: $tenantId,
            documentType: 'sales_invoice',
            documentDate: '2026-06-11',
            lines: [new TaxCalculationLineData(lineNumber: 1, taxableAmount: '100.000000')],
            headerTaxGroupId: (int) $group->getKey(),
        ));

        $this->assertSame('10.000000', $result->headerTaxAmount);
        $this->assertSame('110.000000', $result->totalAmount);
    }

    public function test_posting_profile_resolution_generates_finance_posting_context(): void
    {
        $tenantId = $this->createTenant();
        $tax = $this->createTax($tenantId, 'OUT-TAX', 'VAT', 'exclusive', '15.000000');
        $counterpartyAccount = $this->createFinanceAccount($tenantId, '1100', 'Accounts Receivable', 'debit');
        $taxAccount = $this->createFinanceAccount($tenantId, '2200', 'Tax Payable', 'credit', isTax: true);

        app(TaxMasterDataService::class)->savePostingProfile([
            'tenant_id' => $tenantId,
            'tax_id' => $tax->getKey(),
            'direction' => 'output',
            'account_id' => $taxAccount,
            'active' => true,
        ]);

        $context = app(TaxPostingContextService::class)->build(
            source: new PostingSourceData('sales_invoice', 1001, $tenantId, sourceModule: 'invoice'),
            postingDate: '2026-06-11',
            taxLines: [new TaxAmountData(
                taxId: (int) $tax->getKey(),
                taxCode: 'OUT-TAX',
                taxName: 'Output Tax',
                taxType: 'VAT',
                calculationMethod: 'exclusive',
                rate: '15.000000',
                sequence: 1,
                taxableAmount: '100.000000',
                taxAmount: '15.000000',
                totalAfterTax: '115.000000',
                payable: true,
            )],
            counterpartyAccountCode: '1100',
            counterpartyAccountName: 'Accounts Receivable',
        );

        $this->assertCount(2, $context->financeContext->lines);
        $this->assertSame('1100', $context->financeContext->lines[0]->accountCode);
        $this->assertSame('15.000000', $context->financeContext->lines[0]->debit);
        $this->assertSame('2200', $context->financeContext->lines[1]->accountCode);
        $this->assertSame('15.000000', $context->financeContext->lines[1]->credit);
        $this->assertNotSame($counterpartyAccount, $taxAccount);
    }

    public function test_tax_reports_snapshots_transactions_tenant_and_organization_isolation(): void
    {
        $tenantId = $this->createTenant();
        $orgId = $this->createOrganizationUnit($tenantId, 'ORG-TAX');
        $otherTenant = $this->createTenant('OTHER');
        $tax = $this->createTax($tenantId, 'RPT', 'VAT', 'exclusive', '10.000000', organizationUnitId: $orgId);
        $group = $this->createGroup($tenantId, 'RPT-GROUP', $tax, organizationUnitId: $orgId);

        $calculation = app(TaxCalculationService::class)->calculate(new TaxCalculationData(
            tenantId: $tenantId,
            documentType: 'sales_invoice',
            documentDate: '2026-06-11',
            organizationUnitId: $orgId,
            documentTaxGroupId: (int) $group->getKey(),
            lines: [new TaxCalculationLineData(lineNumber: 1, taxableAmount: '100.000000')],
        ));

        $snapshots = app(TaxSnapshotService::class)->snapshotCalculation($calculation, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgId,
            'source_module' => 'invoice',
            'source_type' => 'sales_invoice',
            'source_id' => 501,
            'source_number' => 'INV-501',
            'source_date' => '2026-06-11',
        ]);
        app(TaxSnapshotService::class)->recordTransaction($snapshots[0], [
            'party_type' => 'customer',
            'party_id' => 10,
            'transaction_date' => '2026-06-11',
        ]);

        TaxDocumentSnapshot::query()->create([
            'tenant_id' => $otherTenant,
            'source_type' => 'sales_invoice',
            'source_id' => 999,
            'tax_code' => 'RPT',
            'tax_name' => 'Other',
            'tax_type' => 'VAT',
            'calculation_method' => 'exclusive',
            'taxable_amount' => '999.000000',
            'tax_amount' => '999.000000',
            'total_amount' => '999.000000',
        ]);

        $summary = app(TaxReportService::class)->summary($tenantId, $orgId, ['date_from' => '2026-06-01', 'date_to' => '2026-06-30']);
        $this->assertSame('100.000000', $summary['totals']['taxable_amount']);
        $this->assertSame('10.000000', $summary['totals']['tax_amount']);

        $reconciliation = app(TaxReportService::class)->reconciliation($tenantId, $orgId);
        $this->assertSame('0.000000', $reconciliation['totals']['difference']);

        $isolated = app(TaxReportService::class)->summary($tenantId, null);
        $this->assertSame('0.000000', $isolated['totals']['tax_amount']);
    }

    public function test_invoice_integration_creates_snapshots_and_posts_transactions_once(): void
    {
        $tenantId = $this->createTenant();
        $customerId = $this->createCustomer($tenantId, 'CUST-INV-TAX');
        $tax = $this->createTax($tenantId, 'INV-TAX', 'VAT', 'exclusive', '10.000000');
        $group = $this->createGroup($tenantId, 'INV-GROUP', $tax);
        $itemId = $this->createItem($tenantId, 'INV-ITEM', defaultTaxGroupId: (int) $group->getKey());

        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_number' => 'INV-TAX-001',
            'invoice_type' => 'sales',
            'direction' => 'outbound',
            'party_type' => 'customer',
            'party_id' => $customerId,
            'invoice_date' => '2026-06-11',
            'status' => 'approved',
            'subtotal' => '200.000000',
            'tax_total' => '20.000000',
            'grand_total' => '220.000000',
            'balance_due' => '220.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoice_lines')->insert([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'line_number' => 1,
            'item_id' => $itemId,
            'description' => 'Taxed item',
            'line_type' => 'item',
            'quantity' => '2.000000',
            'unit_price' => '100.000000',
            'discount_amount' => '0.000000',
            'tax_amount' => '20.000000',
            'charge_amount' => '0.000000',
            'line_total' => '220.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = Invoice::query()->findOrFail($invoiceId);
        $integration = app(TaxDocumentIntegrationService::class);
        $snapshots = $integration->snapshotInvoice($invoice);

        $this->assertCount(1, $snapshots);
        $this->assertSame('INV-TAX', $snapshots[0]->tax_code);
        $this->assertSame('20.000000', (string) $snapshots[0]->tax_amount);

        $invoice->posted_at = now();
        $invoice->save();
        $integration->postInvoice($invoice->refresh());
        $integration->postInvoice($invoice->refresh());

        $this->assertTrue((bool) TaxDocumentSnapshot::query()->whereKey($snapshots[0]->getKey())->value('posted'));
        $this->assertSame(1, TaxTransaction::query()->where('tax_document_snapshot_id', $snapshots[0]->getKey())->count());
        $this->assertSame('customer', TaxTransaction::query()->where('tax_document_snapshot_id', $snapshots[0]->getKey())->value('party_type'));
    }

    public function test_posted_purchase_snapshot_is_immutable_after_rate_change(): void
    {
        $tenantId = $this->createTenant();
        $supplierId = $this->createSupplier($tenantId, 'SUP-SNAP');
        $warehouseId = $this->createWarehouse($tenantId, 'WH-SNAP');
        $tax = $this->createTax($tenantId, 'PUR-SNAP', 'VAT', 'exclusive', '10.000000');
        $group = $this->createGroup($tenantId, 'PUR-SNAP-GROUP', $tax);
        $itemId = $this->createItem($tenantId, 'PUR-SNAP-ITEM', defaultTaxGroupId: (int) $group->getKey());
        $grn = $this->createGoodsReceiptNote($tenantId, $supplierId, $warehouseId, $itemId, '10.000000', '100.000000');

        app(TaxDocumentIntegrationService::class)->postGoodsReceiptNote($grn);
        $snapshot = TaxDocumentSnapshot::query()->where('source_type', 'goods_receipt_note')->where('source_id', $grn->getKey())->firstOrFail();
        $this->assertSame('10.000000', (string) $snapshot->rate);
        $this->assertSame('100.000000', (string) $snapshot->tax_amount);

        DB::table('tax_rates')->where('tax_id', $tax->getKey())->update(['rate' => '20.000000']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Posted tax snapshots cannot be recalculated');
        app(TaxDocumentIntegrationService::class)->snapshotGoodsReceiptNote($grn->refresh());
    }

    public function test_posted_invoice_snapshot_is_immutable_after_rate_change(): void
    {
        $tenantId = $this->createTenant();
        $customerId = $this->createCustomer($tenantId, 'CUST-INV-SNAP');
        $tax = $this->createTax($tenantId, 'INV-SNAP', 'VAT', 'exclusive', '10.000000');
        $group = $this->createGroup($tenantId, 'INV-SNAP-GROUP', $tax);
        $itemId = $this->createItem(
            $tenantId,
            'INV-SNAP-ITEM',
            defaultTaxGroupId: (int) $group->getKey(),
        );
        $invoice = $this->createInvoice(
            $tenantId,
            'sales',
            'outbound',
            'customer',
            $customerId,
            $itemId,
            '100.000000',
        );

        $integration = app(TaxDocumentIntegrationService::class);
        $integration->snapshotInvoice($invoice);
        $integration->postInvoice($invoice->refresh());
        DB::table('tax_rates')->where('tax_id', $tax->getKey())->update([
            'rate' => '20.000000',
        ]);

        $this->assertSame(
            '10.000000',
            (string) TaxDocumentSnapshot::query()
                ->where('source_type', 'invoice')
                ->where('source_id', $invoice->getKey())
                ->value('rate'),
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Posted tax snapshots cannot be recalculated');
        $integration->snapshotInvoice($invoice->refresh());
    }

    public function test_partial_purchase_return_reverses_original_snapshot_tax_and_blocks_duplicate(): void
    {
        $tenantId = $this->createTenant();
        $supplierId = $this->createSupplier($tenantId, 'SUP-RET');
        $warehouseId = $this->createWarehouse($tenantId, 'WH-RET');
        $tax = $this->createTax($tenantId, 'PIN-10', 'VAT', 'exclusive', '10.000000');
        $group = $this->createGroup($tenantId, 'PIN-GROUP', $tax);
        $itemId = $this->createItem($tenantId, 'PIN-ITEM', defaultTaxGroupId: (int) $group->getKey());
        $grn = $this->createGoodsReceiptNote($tenantId, $supplierId, $warehouseId, $itemId, '10.000000', '100.000000');

        app(TaxDocumentIntegrationService::class)->postGoodsReceiptNote($grn);
        $return = $this->createPurchaseReturn($tenantId, $supplierId, $warehouseId, $grn->lines()->firstOrFail()->getKey(), $itemId, '2.000000', '10.000000', '100.000000');
        $created = app(TaxReturnAllocationService::class)->reversePurchaseReturn($return);

        $this->assertCount(1, $created);
        $this->assertSame('-20.000000', (string) $created[0]->tax_amount);
        $summary = app(TaxReportService::class)->summary($tenantId, null, ['tax_code' => 'PIN-10']);
        $this->assertSame('80.000000', $summary['totals']['tax_amount']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax reversal already exists');
        app(TaxReturnAllocationService::class)->reversePurchaseReturn($return->refresh());
    }

    public function test_partial_sales_return_reverses_original_snapshot_tax_and_blocks_duplicate(): void
    {
        $tenantId = $this->createTenant();
        $customerId = $this->createCustomer($tenantId, 'CUST-RET');
        $warehouseId = $this->createWarehouse($tenantId, 'SWH-RET');
        $tax = $this->createTax($tenantId, 'SOUT-10', 'VAT', 'exclusive', '10.000000');
        $group = $this->createGroup($tenantId, 'SOUT-GROUP', $tax);
        $itemId = $this->createItem($tenantId, 'SOUT-ITEM', defaultTaxGroupId: (int) $group->getKey());
        $delivery = $this->createSalesDelivery($tenantId, $customerId, $warehouseId, $itemId, '10.000000', '100.000000');

        app(TaxDocumentIntegrationService::class)->postSalesDelivery($delivery);
        DB::table('tax_rates')->where('tax_id', $tax->getKey())->update([
            'rate' => '20.000000',
        ]);
        $return = $this->createSalesReturn($tenantId, $customerId, $warehouseId, $delivery->lines()->firstOrFail()->getKey(), $itemId, '4.000000', '10.000000', '100.000000');
        $created = app(TaxReturnAllocationService::class)->reverseSalesReturn($return, 123);

        $this->assertCount(1, $created);
        $this->assertSame('10.000000', (string) $created[0]->rate);
        $this->assertSame('-40.000000', (string) $created[0]->tax_amount);
        $this->assertSame(123, $created[0]->metadata['credit_note_id']);
        $summary = app(TaxReportService::class)->summary($tenantId, null, ['tax_code' => 'SOUT-10']);
        $this->assertSame('60.000000', $summary['totals']['tax_amount']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax reversal already exists');
        app(TaxReturnAllocationService::class)->reverseSalesReturn($return->refresh());
    }

    public function test_wht_invoice_context_generates_finance_context_and_missing_profile_fails(): void
    {
        $tenantId = $this->createTenant();
        $supplierId = $this->createSupplier($tenantId, 'SUP-WHT');
        $tax = $this->createTax($tenantId, 'WHT-5', 'WHT', 'percentage', '5.000000', isWithholding: true);
        $group = $this->createGroup($tenantId, 'WHT-GROUP', $tax);
        $itemId = $this->createItem($tenantId, 'WHT-ITEM', defaultTaxGroupId: (int) $group->getKey());
        $invoice = $this->createInvoice($tenantId, 'purchase', 'inbound', 'supplier', $supplierId, $itemId, '100000.000000');

        $integration = app(TaxDocumentIntegrationService::class);
        $integration->snapshotInvoice($invoice);
        $this->assertSame('5000.000000', (string) TaxDocumentSnapshot::query()->where('source_type', 'invoice')->where('is_withholding', true)->value('tax_amount'));

        try {
            $integration->withholdingPostingContextForInvoice($invoice->refresh(), '2026-06-11', '2100', 'Supplier Control');
            $this->fail('Expected missing WHT posting profile to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Tax account mapping is missing', $exception->getMessage());
        }

        $counterpartyAccount = $this->createFinanceAccount($tenantId, '2100', 'Supplier Control', 'credit');
        $taxAccount = $this->createFinanceAccount($tenantId, '2300', 'WHT Control', 'credit', isTax: true);
        app(TaxMasterDataService::class)->savePostingProfile([
            'tenant_id' => $tenantId,
            'tax_id' => $tax->getKey(),
            'direction' => 'withholding',
            'account_id' => $taxAccount,
            'active' => true,
        ]);

        $context = $integration->withholdingPostingContextForInvoice($invoice->refresh(), '2026-06-11', '2100', 'Supplier Control');
        $this->assertCount(2, $context->financeContext->lines);
        $this->assertSame('5000.000000', $context->taxLines[0]->taxAmount);
        $this->assertSame('2300', $context->financeContext->lines[1]->accountCode);
        $this->assertNotSame($counterpartyAccount, $taxAccount);
    }

    public function test_wht_payment_context_uses_allocated_invoice_snapshot_proportion(): void
    {
        $tenantId = $this->createTenant();
        $supplierId = $this->createSupplier($tenantId, 'SUP-PAY-WHT');
        $tax = $this->createTax($tenantId, 'PAY-WHT-5', 'WHT', 'percentage', '5.000000', isWithholding: true);
        $group = $this->createGroup($tenantId, 'PAY-WHT-GROUP', $tax);
        $itemId = $this->createItem($tenantId, 'PAY-WHT-ITEM', defaultTaxGroupId: (int) $group->getKey());
        $invoice = $this->createInvoice($tenantId, 'purchase', 'inbound', 'supplier', $supplierId, $itemId, '100000.000000');
        $payment = $this->createPayment($tenantId, $invoice, '40000.000000');

        $integration = app(TaxDocumentIntegrationService::class);
        $integration->snapshotInvoice($invoice);
        $taxAccount = $this->createFinanceAccount($tenantId, '2350', 'Payment WHT Control', 'credit', isTax: true);
        app(TaxMasterDataService::class)->savePostingProfile([
            'tenant_id' => $tenantId,
            'tax_id' => $tax->getKey(),
            'direction' => 'withholding',
            'account_id' => $taxAccount,
            'active' => true,
        ]);

        $context = $integration->withholdingPostingContextForPayment($payment->refresh(), '2026-06-11', '2100', 'Supplier Control');

        $this->assertSame('payment', $context->source->sourceType);
        $this->assertSame('2000.000000', $context->taxLines[0]->taxAmount);
        $this->assertSame('2350', $context->financeContext->lines[1]->accountCode);
    }

    private function createTax(
        int $tenantId,
        string $code,
        string $type,
        string $method,
        string $rate,
        ?int $organizationUnitId = null,
        bool $isWithholding = false,
    ): Tax {
        $service = app(TaxMasterDataService::class);
        $tax = $service->saveTax($this->taxPayload($tenantId, $code, $code, $type, $method, payable: true, organizationUnitId: $organizationUnitId, isWithholding: $isWithholding));
        $service->saveRate($tax, [
            'rate' => $rate,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
            'active' => true,
        ]);

        return $tax->refresh();
    }

    private function createGroup(int $tenantId, string $code, Tax $tax, bool $isDefault = false, ?int $organizationUnitId = null): TaxGroup
    {
        return app(TaxMasterDataService::class)->saveGroup([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $code,
            'is_default' => $isDefault,
            'active' => true,
        ], [
            ['tax_id' => $tax->getKey(), 'sequence' => 1, 'active' => true],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function taxPayload(
        int $tenantId,
        string $code,
        string $name,
        string $type,
        string $method,
        bool $payable = false,
        ?int $organizationUnitId = null,
        bool $isWithholding = false,
    ): array {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $name,
            'tax_type' => $type,
            'calculation_method' => $method,
            'is_withholding' => $isWithholding,
            'recoverable' => false,
            'payable' => $payable,
            'receivable' => false,
            'active' => true,
        ];
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-TAX-'.$suffix,
            'name' => 'Tax Tenant '.$suffix,
            'slug' => 'tax-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function createOrganizationUnit(int $tenantId, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => $code,
            'code' => $code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(int $tenantId, string $code): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => $code,
            'code' => $code,
            'name' => $code,
            'customer_type' => 'company',
            'status' => 'active',
            'is_credit_allowed' => true,
            'is_advance_allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSupplier(int $tenantId, string $code): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_number' => $code,
            'code' => $code,
            'name' => $code,
            'supplier_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWarehouse(int $tenantId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createItem(int $tenantId, string $code, ?int $defaultTaxGroupId = null): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $code,
            'item_type' => 'stock',
            'tracking_type' => 'none',
            'costing_method' => 'none',
            'default_tax_group_id' => $defaultTaxGroupId,
            'is_stockable' => true,
            'is_combo' => false,
            'is_tax_exempt' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createGoodsReceiptNote(
        int $tenantId,
        int $supplierId,
        int $warehouseId,
        int $itemId,
        string $quantity,
        string $unitPrice,
    ): GoodsReceiptNote {
        $lineTotal = app(DecimalMath::class)->mul($quantity, $unitPrice);
        $grnId = (int) DB::table('goods_receipt_notes')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_type' => 'supplier',
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'grn_number' => 'GRN-TAX-'.Str::upper(Str::random(6)),
            'received_date' => '2026-06-11',
            'status' => 'posted',
            'subtotal' => $lineTotal,
            'grand_total' => $lineTotal,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('goods_receipt_note_lines')->insert([
            'tenant_id' => $tenantId,
            'goods_receipt_note_id' => $grnId,
            'item_id' => $itemId,
            'received_quantity' => $quantity,
            'accepted_quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return GoodsReceiptNote::query()->with('lines')->findOrFail($grnId);
    }

    private function createSalesDelivery(
        int $tenantId,
        int $customerId,
        int $warehouseId,
        int $itemId,
        string $quantity,
        string $unitPrice,
    ): SalesDelivery {
        $lineTotal = app(DecimalMath::class)->mul($quantity, $unitPrice);
        $deliveryId = (int) DB::table('sales_deliveries')->insertGetId([
            'tenant_id' => $tenantId,
            'delivery_number' => 'SD-TAX-'.Str::upper(Str::random(6)),
            'delivery_date' => '2026-06-11',
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sales_delivery_lines')->insert([
            'tenant_id' => $tenantId,
            'sales_delivery_id' => $deliveryId,
            'item_id' => $itemId,
            'delivered_quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SalesDelivery::query()->with('lines')->findOrFail($deliveryId);
    }

    private function createPurchaseReturn(
        int $tenantId,
        int $supplierId,
        int $warehouseId,
        int $sourceLineId,
        int $itemId,
        string $returnedQuantity,
        string $sourceQuantity,
        string $unitPrice,
    ): PurchaseReturn {
        $lineTotal = app(DecimalMath::class)->mul($returnedQuantity, $unitPrice);
        $returnId = (int) DB::table('purchase_returns')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_type' => 'supplier',
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'return_number' => 'PRET-TAX-'.Str::upper(Str::random(6)),
            'return_type' => 'referenced',
            'return_date' => '2026-06-11',
            'status' => 'posted',
            'subtotal' => $lineTotal,
            'grand_total' => $lineTotal,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('purchase_return_lines')->insert([
            'tenant_id' => $tenantId,
            'purchase_return_id' => $returnId,
            'item_id' => $itemId,
            'source_line_type' => 'goods_receipt_note_line',
            'source_line_id' => $sourceLineId,
            'returned_quantity' => $returnedQuantity,
            'source_quantity' => $sourceQuantity,
            'previously_returned_quantity' => '0.000000',
            'remaining_quantity' => app(DecimalMath::class)->sub($sourceQuantity, $returnedQuantity),
            'unit_price' => $unitPrice,
            'cost_basis' => $unitPrice,
            'line_total' => $lineTotal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PurchaseReturn::query()->with('lines')->findOrFail($returnId);
    }

    private function createSalesReturn(
        int $tenantId,
        int $customerId,
        int $warehouseId,
        int $sourceLineId,
        int $itemId,
        string $returnedQuantity,
        string $sourceQuantity,
        string $unitPrice,
    ): SalesReturn {
        $math = app(DecimalMath::class);
        $lineTotal = $math->mul($returnedQuantity, $unitPrice);
        $returnId = (int) DB::table('sales_returns')->insertGetId([
            'tenant_id' => $tenantId,
            'return_number' => 'SRET-TAX-'.Str::upper(Str::random(6)),
            'return_date' => '2026-06-11',
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'return_type' => 'referenced_customer_return',
            'status' => 'posted',
            'subtotal' => $lineTotal,
            'grand_total' => $lineTotal,
            'affects_inventory' => true,
            'affects_customer_balance' => true,
            'approval_required' => false,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sales_return_lines')->insert([
            'tenant_id' => $tenantId,
            'sales_return_id' => $returnId,
            'item_id' => $itemId,
            'source_line_type' => 'sales_delivery_line',
            'source_line_id' => $sourceLineId,
            'returned_quantity' => $returnedQuantity,
            'source_quantity' => $sourceQuantity,
            'previously_returned_quantity' => '0.000000',
            'remaining_quantity' => $math->sub($sourceQuantity, $returnedQuantity),
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'condition_status' => 'sellable',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SalesReturn::query()->with('lines')->findOrFail($returnId);
    }

    private function createInvoice(
        int $tenantId,
        string $invoiceType,
        string $direction,
        string $partyType,
        int $partyId,
        int $itemId,
        string $unitPrice,
    ): Invoice {
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_number' => 'INV-WHT-'.Str::upper(Str::random(6)),
            'invoice_type' => $invoiceType,
            'direction' => $direction,
            'party_type' => $partyType,
            'party_id' => $partyId,
            'invoice_date' => '2026-06-11',
            'status' => 'approved',
            'subtotal' => $unitPrice,
            'grand_total' => $unitPrice,
            'balance_due' => $unitPrice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoice_lines')->insert([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'line_number' => 1,
            'item_id' => $itemId,
            'description' => 'Tax invoice line',
            'line_type' => 'item',
            'quantity' => '1.000000',
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Invoice::query()->with('lines')->findOrFail($invoiceId);
    }

    private function createPayment(int $tenantId, Invoice $invoice, string $allocatedAmount): Payment
    {
        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $invoice->organization_unit_id,
            'payment_number' => 'PAY-WHT-'.Str::upper(Str::random(6)),
            'payment_type' => 'supplier_payment',
            'direction' => 'outbound',
            'party_type' => $invoice->party_type,
            'party_id' => $invoice->party_id,
            'payment_date' => '2026-06-11',
            'exchange_rate' => '1.000000',
            'status' => 'posted',
            'total_amount' => $allocatedAmount,
            'allocated_amount' => $allocatedAmount,
            'unapplied_amount' => '0.000000',
            'refunded_amount' => '0.000000',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_allocations')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $invoice->organization_unit_id,
            'payment_id' => $paymentId,
            'invoice_id' => $invoice->getKey(),
            'invoice_total' => (string) $invoice->grand_total,
            'invoice_balance_before' => (string) $invoice->grand_total,
            'previously_allocated_amount' => '0.000000',
            'allocated_amount' => $allocatedAmount,
            'invoice_balance_after' => app(DecimalMath::class)->sub((string) $invoice->grand_total, $allocatedAmount),
            'allocation_date' => '2026-06-11',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Payment::query()->with('allocations')->findOrFail($paymentId);
    }

    private function createFinanceAccount(int $tenantId, string $code, string $name, string $normalBalance, bool $isTax = false): int
    {
        $typeId = (int) DB::table('finance_account_types')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'TYPE-'.$code,
            'name' => 'Type '.$code,
            'normal_balance' => $normalBalance,
            'statement_type' => 'balance_sheet',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('finance_accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'account_type_id' => $typeId,
            'code' => $code,
            'name' => $name,
            'normal_balance' => $normalBalance,
            'is_posting_account' => true,
            'is_tax_account' => $isTax,
            'is_active' => true,
            'opening_balance' => '0.000000',
            'current_balance' => '0.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
