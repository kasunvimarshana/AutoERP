<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceStatusService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class InvoiceSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_and_reverses_payment_allocations_through_invoice_owned_service(): void
    {
        $tenantId = $this->createTenant();
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
        $postingPlan = app(InvoicePostingPlanFactory::class)->outbound(
            FinancePostingProfileCode::SalesInvoice,
            '2026-06-06',
            FinanceAccountRoleCode::Revenue,
            '1000.000000',
            description: 'Settlement invoice',
        );
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-SETTLE-CONTRACT',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Settlement invoice',
                    quantity: '1.000000',
                    unitPrice: '1000.000000',
                ),
            ],
            postingPlan: $postingPlan,
        )));

        $statuses = app(InvoiceStatusService::class);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => $statuses->transition($invoice, InvoiceStatus::Approved));
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => $statuses->transition($invoice, InvoiceStatus::Posted));

        $settlements = app(InvoiceSettlementServiceInterface::class);
        $applied = $this->withTenantExecutionContext($tenantId, fn () => $settlements->applyPaymentAllocation((int) $invoice->getKey(), '400.000000'));

        $this->assertSame('1000.000000', $applied->balanceBefore);
        $this->assertSame('600.000000', $applied->balanceAfter);

        $reversed = $this->withTenantExecutionContext($tenantId, fn () => $settlements->reversePaymentAllocation((int) $invoice->getKey(), '400.000000'));

        $this->assertSame('600.000000', $reversed->balanceBefore);
        $this->assertSame('1000.000000', $reversed->balanceAfter);
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-ISS-'.$suffix,
            'name' => 'Invoice Settlement '.$suffix,
            'slug' => 'invoice-settlement-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
