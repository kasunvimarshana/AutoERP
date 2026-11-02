<?php

declare(strict_types=1);

namespace Tests\Feature\Invoice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceBalanceService;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Enums\SupplierType;
use Tests\TestCase;

final class InvoiceEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_progressive_invoices_and_allocates_header_adjustments(): void
    {
        $tenantId = $this->createTenant();
        $supplierId = $this->createSupplier($tenantId);
        $creator = app(InvoiceCreationService::class);

        $invoiceOne = $this->withTenantExecutionContext($tenantId, fn () => $creator->create($this->progressiveInvoiceData(
            tenantId: $tenantId,
            supplierId: $supplierId,
            invoiceNumber: 'INV-001',
            invoiceQuantity: '40.000000',
            expectedLineTotal: '40000.000000',
        )));

        $this->assertSame('40000.000000', (string) $invoiceOne->subtotal);
        $this->assertSame('2000.000000', (string) $invoiceOne->discount_total);
        $this->assertSame('7200.000000', (string) $invoiceOne->tax_total);
        $this->assertSame('4000.000000', (string) $invoiceOne->charge_total);
        $this->assertSame('49200.000000', (string) $invoiceOne->grand_total);
        $this->assertSame('49200.000000', (string) $invoiceOne->balance->remaining_amount);

        $this->assertDatabaseHas('invoice_source_lines', [
            'invoice_id' => $invoiceOne->getKey(),
            'source_line_type' => 'normalized_source_line',
            'source_line_id' => 9001,
            'invoiced_quantity' => '40.000000',
            'remaining_quantity' => '60.000000',
        ]);

        $this->assertDatabaseHas('invoice_adjustment_allocations', [
            'invoice_id' => $invoiceOne->getKey(),
            'source_adjustment_id' => 7001,
            'allocated_amount' => '2000.000000',
            'remaining_amount' => '3000.000000',
        ]);

        $invoiceTwo = $this->withTenantExecutionContext($tenantId, fn () => $creator->create($this->progressiveInvoiceData(
            tenantId: $tenantId,
            supplierId: $supplierId,
            invoiceNumber: 'INV-002',
            invoiceQuantity: '60.000000',
            expectedLineTotal: '60000.000000',
        )));

        $this->assertSame('60000.000000', (string) $invoiceTwo->subtotal);
        $this->assertSame('3000.000000', (string) $invoiceTwo->discount_total);
        $this->assertSame('10800.000000', (string) $invoiceTwo->tax_total);
        $this->assertSame('6000.000000', (string) $invoiceTwo->charge_total);
        $this->assertSame('73800.000000', (string) $invoiceTwo->grand_total);

        $this->assertDatabaseHas('invoice_adjustment_allocations', [
            'invoice_id' => $invoiceTwo->getKey(),
            'source_adjustment_id' => 7001,
            'previously_allocated_amount' => '2000.000000',
            'allocated_amount' => '3000.000000',
            'remaining_amount' => '0.000000',
        ]);
    }

    public function test_it_prevents_over_invoicing_source_lines(): void
    {
        $tenantId = $this->createTenant();
        $supplierId = $this->createSupplier($tenantId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice quantity cannot exceed source remaining quantity.');

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create($this->progressiveInvoiceData(
            tenantId: $tenantId,
            supplierId: $supplierId,
            invoiceNumber: 'INV-OVER',
            invoiceQuantity: '101.000000',
            expectedLineTotal: '101000.000000',
        )));
    }

    public function test_it_blocks_invalid_status_transitions(): void
    {
        $statusService = app(InvoiceStatusService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice status cannot transition from draft to paid.');

        $statusService->assertCanTransition(InvoiceStatus::Draft, InvoiceStatus::Paid);
    }

    public function test_it_updates_balance_after_payment_without_payment_ownership(): void
    {
        $tenantId = $this->createTenant();
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-PAY',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Manual service charge',
                    quantity: '1.000000',
                    unitPrice: '1000.000000',
                ),
            ],
        )));

        $statusService = app(InvoiceStatusService::class);
        $balanceService = app(InvoiceBalanceService::class);
        $balance = $this->withTenantExecutionContext($tenantId, function () use ($statusService, $balanceService, $invoice) {
            $invoice = $statusService->transition($invoice, InvoiceStatus::Approved);
            $invoice = $statusService->transition($invoice, InvoiceStatus::Posted);

            return $balanceService->applyPayment($invoice, '400.000000');
        });

        $this->assertSame('600.000000', (string) $balance->remaining_amount);
        $this->assertSame(
            InvoiceStatus::PartiallyPaid,
            $this->withTenantExecutionContext($tenantId, fn () => $invoice->refresh()->status),
        );

        $balance = $this->withTenantExecutionContext(
            $tenantId,
            fn () => $balanceService->applyPayment($invoice->refresh(), '600.000000'),
        );

        $this->assertSame('0.000000', (string) $balance->remaining_amount);
        $this->assertSame(
            InvoiceStatus::Paid,
            $this->withTenantExecutionContext($tenantId, fn () => $invoice->refresh()->status),
        );
    }

    public function test_it_prevents_cross_tenant_sources(): void
    {
        $tenantId = $this->createTenant('A');
        $otherTenantId = $this->createTenant('B');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice source tenant must match invoice tenant.');

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Purchase,
            direction: InvoiceDirection::Inbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-CROSS',
            sources: [
                new InvoiceSourceData(
                    tenantId: $otherTenantId,
                    sourceType: 'normalized_source',
                    sourceId: 1001,
                ),
            ],
        )));
    }

    public function test_it_rejects_inconsistent_caller_supplied_line_totals(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invoice line total must match its quantity, price, discount, tax, and charge.',
        );

        $tenantId = $this->createTenant();
        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-BAD-LINE-TOTAL',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Inconsistent total',
                    quantity: '2.000000',
                    unitPrice: '50.000000',
                    lineTotal: '99.000000',
                ),
            ],
        )));
    }

    public function test_it_requires_source_lines_to_reference_declared_sources(): void
    {
        $tenantId = $this->createTenant();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invoice source line must reference an invoice source.',
        );

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Purchase,
            direction: InvoiceDirection::Inbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-ORPHAN-SOURCE-LINE',
            sourceLines: [
                new InvoiceSourceLineData(
                    tenantId: $tenantId,
                    sourceType: 'purchase_order',
                    sourceId: 100,
                    sourceLineType: 'purchase_order_line',
                    sourceLineId: 200,
                    sourceQuantity: '1.000000',
                    invoicedQuantity: '1.000000',
                    sourceUnitPrice: '10.000000',
                    sourceLineTotal: '10.000000',
                ),
            ],
        )));
    }

    public function test_cancelled_invoice_restores_source_quantity_and_adjustment_capacity(): void
    {
        $tenantId = $this->createTenant();
        $supplierId = $this->createSupplier($tenantId);
        $creator = app(InvoiceCreationService::class);
        $first = $this->withTenantExecutionContext($tenantId, fn () => $creator->create($this->progressiveInvoiceData(
            tenantId: $tenantId,
            supplierId: $supplierId,
            invoiceNumber: 'INV-CANCELLED-SOURCE',
            invoiceQuantity: '40.000000',
            expectedLineTotal: '40000.000000',
        )));

        $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(InvoiceStatusService::class)->transition($first, InvoiceStatus::Cancelled),
        );

        $replacement = $this->withTenantExecutionContext($tenantId, fn () => $creator->create($this->progressiveInvoiceData(
            tenantId: $tenantId,
            supplierId: $supplierId,
            invoiceNumber: 'INV-REPLACEMENT-SOURCE',
            invoiceQuantity: '100.000000',
            expectedLineTotal: '100000.000000',
        )));

        $this->assertDatabaseHas('invoice_source_lines', [
            'invoice_id' => $replacement->getKey(),
            'previously_invoiced_quantity' => '0.000000',
            'invoiced_quantity' => '100.000000',
            'remaining_quantity' => '0.000000',
        ]);
        $this->assertDatabaseHas('invoice_adjustment_allocations', [
            'invoice_id' => $replacement->getKey(),
            'source_adjustment_id' => 7001,
            'previously_allocated_amount' => '0.000000',
            'allocated_amount' => '5000.000000',
            'remaining_amount' => '0.000000',
        ]);
    }

    public function test_it_prevents_cross_organization_sources(): void
    {
        $tenantId = $this->createTenant();
        $organizationUnitId = $this->createOrganizationUnit($tenantId, 'INV-ORG-A');
        $otherOrganizationUnitId = $this->createOrganizationUnit($tenantId, 'INV-ORG-B');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invoice source organization unit must match invoice organization unit.',
        );

        $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-CROSS-ORG',
            organizationUnitId: $organizationUnitId,
            sources: [
                new InvoiceSourceData(
                    tenantId: $tenantId,
                    sourceType: 'manual_source',
                    sourceId: 1,
                    organizationUnitId: $otherOrganizationUnitId,
                ),
            ],
        )));
    }

    private function progressiveInvoiceData(
        int $tenantId,
        int $supplierId,
        string $invoiceNumber,
        string $invoiceQuantity,
        string $expectedLineTotal,
    ): CreateInvoiceData {
        return new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Purchase,
            direction: InvoiceDirection::Inbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: $invoiceNumber,
            partyType: 'supplier',
            partyId: $supplierId,
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Normalized source line',
                    quantity: $invoiceQuantity,
                    unitPrice: '1000.000000',
                    lineTotal: $expectedLineTotal,
                    sourceLineType: 'normalized_source_line',
                    sourceLineId: 9001,
                ),
            ],
            sources: [
                new InvoiceSourceData(
                    tenantId: $tenantId,
                    sourceType: 'normalized_source',
                    sourceId: 1001,
                    sourceDocumentNumber: 'SRC-001',
                    sourceDocumentDate: '2026-06-06',
                    sourceSubtotal: '100000.000000',
                    sourceAdjustmentTotal: '23000.000000',
                    sourceGrandTotal: '123000.000000',
                ),
            ],
            sourceLines: [
                new InvoiceSourceLineData(
                    tenantId: $tenantId,
                    sourceType: 'normalized_source',
                    sourceId: 1001,
                    sourceLineType: 'normalized_source_line',
                    sourceLineId: 9001,
                    sourceQuantity: '100.000000',
                    invoicedQuantity: $invoiceQuantity,
                    sourceUnitPrice: '1000.000000',
                    sourceLineTotal: '100000.000000',
                    invoicedLineTotal: $expectedLineTotal,
                ),
            ],
            adjustments: [
                new InvoiceAdjustmentData(
                    name: 'Source discount',
                    adjustmentType: AdjustmentType::Discount,
                    effect: AdjustmentEffect::Decrease,
                    amount: '5000.000000',
                    sourceAdjustmentType: 'normalized_adjustment',
                    sourceAdjustmentId: 7001,
                    sourceType: 'normalized_source',
                    sourceId: 1001,
                    allocationMethod: AllocationMethod::Proportional,
                    isSystemGenerated: true,
                ),
                new InvoiceAdjustmentData(
                    name: 'Source VAT',
                    adjustmentType: AdjustmentType::Tax,
                    effect: AdjustmentEffect::Increase,
                    amount: '18000.000000',
                    sourceAdjustmentType: 'normalized_adjustment',
                    sourceAdjustmentId: 7002,
                    sourceType: 'normalized_source',
                    sourceId: 1001,
                    allocationMethod: AllocationMethod::Proportional,
                    isSystemGenerated: true,
                ),
                new InvoiceAdjustmentData(
                    name: 'Source freight',
                    adjustmentType: AdjustmentType::Freight,
                    effect: AdjustmentEffect::Increase,
                    amount: '10000.000000',
                    sourceAdjustmentType: 'normalized_adjustment',
                    sourceAdjustmentId: 7003,
                    sourceType: 'normalized_source',
                    sourceId: 1001,
                    allocationMethod: AllocationMethod::Proportional,
                    isSystemGenerated: true,
                ),
            ],
        );
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function createSupplier(int $tenantId): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'supplier_number' => 'SUP-'.$suffix,
            'code' => 'SUP-'.$suffix,
            'name' => 'Supplier '.$suffix,
            'legal_name' => 'Supplier '.$suffix,
            'display_name' => 'Supplier '.$suffix,
            'supplier_type' => SupplierType::Company->value,
            'status' => SupplierStatus::Active->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrganizationUnit(int $tenantId, string $name): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => $name,
            'code' => $name,
            'depth' => 0,
            'is_active' => true,
            '_lft' => 0,
            '_rgt' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
