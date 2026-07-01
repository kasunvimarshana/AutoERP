<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceSourceAllocationGuardService;
use Tests\TestCase;

final class InvoiceSourceAllocationGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_one_stable_guard_for_repeated_source_line_locks(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationUnitId): void {
            $data = $this->invoiceData($tenantId, $organizationUnitId);

            DB::transaction(fn () => app(InvoiceSourceAllocationGuardService::class)->lock($data));
            DB::transaction(fn () => app(InvoiceSourceAllocationGuardService::class)->lock($data));

            $this->assertDatabaseCount('invoice_source_allocation_guards', 1);
            $this->assertDatabaseHas('invoice_source_allocation_guards', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'source_type' => 'sales_delivery',
                'source_id' => 101,
                'source_line_type' => 'sales_delivery_line',
                'source_line_id' => 501,
            ]);
        });
    }

    public function test_it_rejects_source_lines_from_a_different_scope(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice source line scope does not match the invoice scope.');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationUnitId): void {
            $data = new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-07-02',
                organizationUnitId: $organizationUnitId,
                sourceLines: [new InvoiceSourceLineData(
                    tenantId: $tenantId + 1,
                    sourceType: 'sales_delivery',
                    sourceId: 101,
                    sourceLineType: 'sales_delivery_line',
                    sourceLineId: 501,
                    sourceQuantity: '10.000000',
                    invoicedQuantity: '1.000000',
                    sourceUnitPrice: '25.000000',
                    sourceLineTotal: '250.000000',
                    organizationUnitId: $organizationUnitId,
                )],
            );

            app(InvoiceSourceAllocationGuardService::class)->lock($data);
        });
    }

    private function invoiceData(int $tenantId, int $organizationUnitId): CreateInvoiceData
    {
        return new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Sales,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-07-02',
            organizationUnitId: $organizationUnitId,
            sourceLines: [new InvoiceSourceLineData(
                tenantId: $tenantId,
                sourceType: 'sales_delivery',
                sourceId: 101,
                sourceLineType: 'sales_delivery_line',
                sourceLineId: 501,
                sourceQuantity: '10.000000',
                invoicedQuantity: '1.000000',
                sourceUnitPrice: '25.000000',
                sourceLineTotal: '250.000000',
                organizationUnitId: $organizationUnitId,
            )],
        );
    }

    /** @return array{0:int,1:int} */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-IAG-'.$suffix,
            'name' => 'Invoice Guard '.$suffix,
            'slug' => 'invoice-guard-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Invoice Guard Org',
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId];
    }
}
