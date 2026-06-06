<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Tests\TestCase;

final class InvoiceBalanceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_invoice_balance_through_contract(): void
    {
        $invoice = app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $this->createTenant(),
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            invoiceNumber: 'INV-BAL-CONTRACT',
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Contract balance invoice',
                    quantity: '1.000000',
                    unitPrice: '1250.000000',
                ),
            ],
        ));

        $provider = app(InvoiceBalanceProviderInterface::class);

        $invoiceBalance = $provider->getInvoiceBalance((int) $invoice->getKey());
        $sharedBalance = $provider->getBalance((int) $invoice->getKey());

        $this->assertSame((int) $invoice->getKey(), $invoiceBalance->invoiceId);
        $this->assertSame('1250.000000', $invoiceBalance->invoiceTotal);
        $this->assertSame('1250.000000', $invoiceBalance->remainingAmount);
        $this->assertSame('1250.000000', $sharedBalance->remainingAmount);
        $this->assertSame('draft', $provider->getInvoiceStatus((int) $invoice->getKey()));
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-IBP-'.$suffix,
            'name' => 'Invoice Balance Provider '.$suffix,
            'slug' => 'invoice-balance-provider-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
