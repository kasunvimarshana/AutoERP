<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Contracts\InvoicePaymentMethodProviderInterface;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class InvoicePaymentMethodProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_unique_methods_from_active_invoice_allocations_only(): void
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Payment Method Print Unit',
            'code' => 'PM-PRINT-'.$suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'invoice_number' => 'INV-'.$suffix,
            'invoice_type' => 'service',
            'direction' => 'outbound',
            'invoice_date' => '2026-09-18',
            'grand_total' => '100.000000',
            'balance_due' => '40.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $methodId = (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'scope_key' => $tenantId.':'.$organizationUnitId,
            'code' => 'CASH-'.$suffix,
            'name' => 'Cash',
            'method_type' => 'cash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_number' => 'PAY-'.$suffix,
            'payment_type' => 'receipt',
            'direction' => 'inbound',
            'document_status' => 'approved',
            'allocation_status' => 'allocated',
            'payment_date' => '2026-09-18',
            'total_amount' => '60.000000',
            'allocated_amount' => '60.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_id' => $paymentId,
            'line_number' => 1,
            'payment_method_id' => $methodId,
            'payment_method_code_snapshot' => 'CASH',
            'payment_method_name_snapshot' => 'Cash',
            'payment_method_type_snapshot' => 'cash',
            'amount' => '60.000000',
            'status' => 'cleared',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_allocations')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'invoice_number_snapshot' => 'INV-'.$suffix,
            'invoice_total' => '100.000000',
            'invoice_balance_before' => '100.000000',
            'allocated_amount' => '60.000000',
            'invoice_balance_after' => '40.000000',
            'allocation_date' => '2026-09-18',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $names = app(InvoicePaymentMethodProviderInterface::class)->namesForInvoice(
            $invoiceId,
            $tenantId,
            $organizationUnitId,
        );

        self::assertSame(['Cash'], $names);
    }
}
