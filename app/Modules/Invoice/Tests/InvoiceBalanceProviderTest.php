<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Services\InvoiceCreationService;
use Tests\TestCase;

final class InvoiceBalanceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_invoice_balance_through_contract(): void
    {
        $tenantId = $this->createTenant();
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
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
        )));

        $provider = app(InvoiceBalanceProviderInterface::class);

        $invoiceBalance = $this->withTenantExecutionContext($tenantId, fn () => $provider->getInvoiceBalance((int) $invoice->getKey()));
        $sharedBalance = $this->withTenantExecutionContext($tenantId, fn () => $provider->getBalance((int) $invoice->getKey()));

        $this->assertSame((int) $invoice->getKey(), $invoiceBalance->invoiceId);
        $this->assertSame('1250.000000', $invoiceBalance->invoiceTotal);
        $this->assertSame('1250.000000', $invoiceBalance->remainingAmount);
        $this->assertSame('1250.000000', $sharedBalance->remainingAmount);
        $this->assertSame('draft', $this->withTenantExecutionContext($tenantId, fn () => $provider->getInvoiceStatus((int) $invoice->getKey())));
    }

    public function test_payable_state_validation_is_scoped_before_balance_is_returned(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant();
        $customerId = $this->createCustomer($tenantId, null);
        $invoice = $this->withTenantExecutionContext($tenantId, fn () => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-07',
            invoiceNumber: 'INV-BAL-SCOPED',
            partyType: 'customer',
            partyId: $customerId,
            status: InvoiceStatus::Posted,
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Scoped payable invoice',
                    quantity: '1.000000',
                    unitPrice: '750.000000',
                ),
            ],
        )));

        $provider = app(InvoiceBalanceProviderInterface::class);

        $balance = $this->withTenantExecutionContext($tenantId, fn () => $provider->validatePayableState(
            invoiceId: (int) $invoice->getKey(),
            tenantId: $tenantId,
            organizationUnitId: null,
            partyType: 'customer',
            partyId: $customerId,
        ));

        $this->assertSame((int) $invoice->getKey(), $balance->sourceId);
        $this->assertSame('750.000000', $balance->remainingAmount);

        $this->expectException(ModelNotFoundException::class);

        $this->withTenantExecutionContext($tenantId, fn () => $provider->validatePayableState(
            invoiceId: (int) $invoice->getKey(),
            tenantId: $otherTenantId,
            organizationUnitId: null,
            partyType: 'customer',
            partyId: $customerId,
        ));
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
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(int $tenantId, ?int $organizationUnitId): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUS-IBP-'.$suffix,
            'code' => 'CUS-IBP-'.$suffix,
            'name' => 'Invoice Balance Provider Customer '.$suffix,
            'legal_name' => 'Invoice Balance Provider Customer '.$suffix.' Legal',
            'display_name' => 'Invoice Balance Provider Customer '.$suffix,
            'customer_type' => 'company',
            'status' => 'active',
            'email' => 'invoice-balance-provider-customer-'.Str::lower($suffix).'@example.test',
            'phone' => '0111234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
