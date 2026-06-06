<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Tests\TestCase;

final class InvoiceSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_and_reverses_payment_allocations_through_invoice_owned_service(): void
    {
        $invoice = app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $this->createTenant(),
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
        ));

        $statuses = app(InvoiceStatusService::class);
        $invoice = $statuses->transition($invoice, InvoiceStatus::Approved);
        $invoice = $statuses->transition($invoice, InvoiceStatus::Posted);

        $settlements = app(InvoiceSettlementServiceInterface::class);
        $applied = $settlements->applyPaymentAllocation((int) $invoice->getKey(), '400.000000');

        $this->assertSame('1000.000000', $applied->balanceBefore);
        $this->assertSame('600.000000', $applied->balanceAfter);

        $reversed = $settlements->reversePaymentAllocation((int) $invoice->getKey(), '400.000000');

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
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
