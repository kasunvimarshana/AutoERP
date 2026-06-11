<?php

declare(strict_types=1);

namespace Modules\Tax\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Invoice\Models\Invoice;
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
use Modules\Tax\Services\TaxDocumentIntegrationService;
use Modules\Tax\Services\TaxDeterminationService;
use Modules\Tax\Services\TaxMasterDataService;
use Modules\Tax\Services\TaxPostingContextService;
use Modules\Tax\Services\TaxReportService;
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

    private function createTax(
        int $tenantId,
        string $code,
        string $type,
        string $method,
        string $rate,
        ?int $organizationUnitId = null,
    ): Tax {
        $service = app(TaxMasterDataService::class);
        $tax = $service->saveTax($this->taxPayload($tenantId, $code, $code, $type, $method, payable: true, organizationUnitId: $organizationUnitId));
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
    ): array {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $name,
            'tax_type' => $type,
            'calculation_method' => $method,
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
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrganizationUnit(int $tenantId, string $code): int
    {
        return (int) DB::table('organization_units')->insertGetId([
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
