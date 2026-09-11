<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceSourceAllocationService;
use Tests\TestCase;

final class InvoiceSourceAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    private const SOURCE_TYPE = 'allocation_test';

    private const SOURCE_LINE_TYPE = 'allocation_test_line';

    public function test_exact_division_does_not_lose_an_intermediate_ratio_remainder(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $row = $this->allocate($tenant, total: '3');
            $this->assertSame('1.000000', $row['invoiced_line_total']);
        });
    }

    public function test_successive_allocations_reconcile_to_the_original_amount(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $amounts = [];
            foreach (['0.333333', '0.333333', '0.333334'] as $expected) {
                $row = $this->allocate($tenant);
                $amounts[] = $row['invoiced_line_total'];
                $this->assertSame($expected, $row['invoiced_line_total']);
                $this->persist($tenant, $row['invoiced_line_total']);
            }
            $this->assertSame('1.000000', app(DecimalMath::class)->sum($amounts));
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Invoice quantity cannot exceed source remaining quantity.');
            $this->allocate($tenant);
        });
    }

    public function test_released_quantity_uses_surviving_amounts_without_rewriting_history(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $released = $this->persist($tenant, '0.333333');
            $this->persist($tenant, '0.333333');
            $this->persist($tenant, '0.333334');
            // Fixture for allocation history after the owning reversal workflow releases a source.
            DB::table('invoices')->where('id', $released)->update(['status' => InvoiceStatus::Reversed->value]);
            $row = $this->allocate($tenant);
            $this->assertSame('2.000000', $row['previously_invoiced_quantity']);
            $this->assertSame('0.333333', $row['invoiced_line_total']);
            $this->assertDatabaseCount('invoice_source_lines', 3);
        });
    }

    public function test_default_catches_up_existing_rounding_and_ignores_client_previous_quantity(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $this->persist($tenant, '0.999999', total: '3');
            $row = $this->allocate($tenant, total: '3');
            $this->assertSame('1.000000', $row['previously_invoiced_quantity']);
            $this->assertSame('1.000001', $row['invoiced_line_total']);
            $this->assertSame('0.999999', (string) DB::table('invoice_source_lines')->value('invoiced_line_total'));
        });
    }

    public function test_explicit_source_amount_remains_the_source_owners_decision(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $this->persist($tenant, '0.9');
            $data = $this->data($tenant, amount: '0.05');
            $row = app(InvoiceSourceAllocationService::class)->prepareSourceLineAllocations($data)[0];
            $this->assertSame('0.05', $row['invoiced_line_total']);
        });
    }

    public function test_default_rejects_negative_catchup_after_nonproportional_allocations(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $this->persist($tenant, '0.9');
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Provide an explicit invoiced line total');
            $this->allocate($tenant);
        });
    }

    public function test_another_tenant_cannot_consume_this_source(): void
    {
        $first = $this->tenant();
        $second = $this->tenant();
        $this->withTenantExecutionContext($first, fn () => $this->persist($first, '0.333333'));
        $this->withTenantExecutionContext($second, function () use ($second): void {
            $row = $this->allocate($second);
            $this->assertSame('0.000000', $row['previously_invoiced_quantity']);
            $this->assertSame('0.333333', $row['invoiced_line_total']);
        });
    }

    public function test_fractional_source_quantity_preserves_the_smallest_persisted_amount(): void
    {
        $tenant = $this->tenant();
        $this->withTenantExecutionContext($tenant, function () use ($tenant): void {
            $data = $this->data($tenant, total: '0.000001', sourceQuantity: '0.5', selectedQuantity: '0.5');
            $row = app(InvoiceSourceAllocationService::class)->prepareSourceLineAllocations($data)[0];
            $this->assertSame('0.000001', $row['invoiced_line_total']);
            $this->assertSame('0.000000', $row['remaining_quantity']);
        });
    }

    private function allocate(int $tenant, string $total = '1'): array
    {
        return app(InvoiceSourceAllocationService::class)->prepareSourceLineAllocations($this->data($tenant, total: $total))[0];
    }

    private function persist(int $tenant, string $amount, string $total = '1'): int
    {
        return (int) app(InvoiceCreationService::class)->create($this->data($tenant, $total, $amount))->id;
    }

    private function data(int $tenant, string $total = '1', ?string $amount = null, string $sourceQuantity = '3', string $selectedQuantity = '1'): CreateInvoiceData
    {
        return new CreateInvoiceData(
            tenantId: $tenant,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-09-11',
            invoiceNumber: 'ALLOC-'.Str::uuid(),
            lines: [new InvoiceLineData(lineNumber: 1, description: 'Allocation test', quantity: '1', unitPrice: $amount ?? '0',
                sourceLineType: self::SOURCE_LINE_TYPE, sourceLineId: 1)],
            sources: [new InvoiceSourceData(tenantId: $tenant, sourceType: self::SOURCE_TYPE, sourceId: 1)],
            sourceLines: [new InvoiceSourceLineData(tenantId: $tenant, sourceType: self::SOURCE_TYPE, sourceId: 1,
                sourceLineType: self::SOURCE_LINE_TYPE, sourceLineId: 1, sourceQuantity: $sourceQuantity, invoicedQuantity: $selectedQuantity,
                sourceUnitPrice: '0', sourceLineTotal: $total, previouslyInvoicedQuantity: '999', invoicedLineTotal: $amount)],
        );
    }

    private function tenant(): int
    {
        $key = (string) Str::uuid();

        return (int) DB::table('tenants')->insertGetId(['uuid' => $key, 'code' => $key, 'name' => 'Allocation test',
            'slug' => $key, 'status' => 'active', 'status_changed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
