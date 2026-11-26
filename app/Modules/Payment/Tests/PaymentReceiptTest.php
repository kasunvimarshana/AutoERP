<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentReceiptService;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_compile_receipt_for_inbound_customer_payment(): void
    {
        [$tenantId, $payment] = $this->paymentFixture(PaymentDirection::Inbound, PaymentType::CustomerReceipt);

        $this->withTenantExecutionContext($tenantId, function () use ($payment): void {
            $service = app(PaymentReceiptService::class);
            $receiptData = $service->getReceiptData($payment);

            self::assertSame('OFFICIAL PAYMENT RECEIPT', $receiptData['document_title']);
            self::assertTrue($receiptData['is_inbound']);
            self::assertSame($payment->payment_number, $receiptData['payment_number']);
            self::assertSame('15000.000000', $receiptData['total_amount']);
            self::assertNotEmpty($receiptData['amount_in_words']);
            self::assertStringNotContainsString('Only Only', $receiptData['amount_in_words']);
            self::assertStringEndsWith('Only', $receiptData['amount_in_words']);
            self::assertSame('Test Customer', $receiptData['party']['name']);
            self::assertCount(1, $receiptData['lines']);
            self::assertSame('Cash', $receiptData['lines'][0]['method_name']);

            // Verify Blade view renders without error
            $html = view('payment.receipt', $receiptData)->render();
            self::assertStringContainsString('OFFICIAL PAYMENT RECEIPT', $html);
            self::assertStringContainsString($payment->payment_number, $html);
            self::assertStringContainsString('Test Customer', $html);
        });
    }

    public function test_compile_receipt_for_outbound_supplier_disbursement(): void
    {
        [$tenantId, $payment] = $this->paymentFixture(PaymentDirection::Outbound, PaymentType::SupplierPayment);

        $this->withTenantExecutionContext($tenantId, function () use ($payment): void {
            $service = app(PaymentReceiptService::class);
            $receiptData = $service->getReceiptData($payment);

            self::assertSame('DISBURSEMENT VOUCHER', $receiptData['document_title']);
            self::assertFalse($receiptData['is_inbound']);
            self::assertSame('Test Supplier', $receiptData['party']['name']);

            $html = view('payment.receipt', $receiptData)->render();
            self::assertStringContainsString('DISBURSEMENT VOUCHER', $html);
            self::assertStringContainsString('Test Supplier', $html);
        });
    }

    public function test_compile_receipt_with_allocations_and_source_document_resolution(): void
    {
        [$tenantId, $payment] = $this->paymentFixture(PaymentDirection::Inbound, PaymentType::CustomerReceipt);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            // Create an invoice
            $invoiceId = DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payment->organization_unit_id,
                'invoice_number' => 'INV-2026-9999',
                'invoice_type' => 'standard',
                'direction' => 'outbound',
                'party_type' => 'customer',
                'party_id' => $payment->party_id,
                'status' => 'posted',
                'invoice_date' => '2026-11-10',
                'due_date' => '2026-11-20',
                'currency_id' => $payment->currency_id,
                'exchange_rate' => '1.000000',
                'subtotal' => '15000.000000',
                'tax_total' => '0.000000',
                'grand_total' => '15000.000000',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create invoice source with agreement reference
            DB::table('invoice_sources')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payment->organization_unit_id,
                'invoice_id' => $invoiceId,
                'source_type' => 'vehicle_rental_agreement',
                'source_id' => 12345,
                'source_document_number' => 'AGR-RENTAL-2026-001',
                'source_document_date' => '2026-11-01',
                'source_subtotal' => '15000.000000',
                'source_grand_total' => '15000.000000',
                'invoiced_amount' => '15000.000000',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create allocation with snapshot
            DB::table('payment_allocations')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payment->organization_unit_id,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'active_identity_slot' => 1,
                'invoice_number_snapshot' => 'INV-2026-9999',
                'invoice_date_snapshot' => '2026-11-10',
                'invoice_currency_code_snapshot' => 'LKR',
                'invoice_exchange_rate_snapshot' => '1.000000',
                'invoice_total' => '15000.000000',
                'invoice_balance_before' => '15000.000000',
                'previously_allocated_amount' => '0.000000',
                'allocated_amount' => '15000.000000',
                'invoice_balance_after' => '0.000000',
                'allocation_date' => '2026-11-15',
                'allocation_method' => 'specific_invoice',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(PaymentReceiptService::class);
            $receiptData = $service->getReceiptData($payment->fresh());

            self::assertSame('AGR-RENTAL-2026-001', $receiptData['agreement_reference']);
            self::assertCount(1, $receiptData['allocations']);
            self::assertSame('INV-2026-9999', $receiptData['allocations'][0]['invoice_number']);
            self::assertSame('2026-11-10', $receiptData['allocations'][0]['invoice_date']);
            self::assertSame('15000.000000', $receiptData['allocations'][0]['allocated_amount']);

            $html = view('payment.receipt', $receiptData)->render();
            self::assertStringContainsString('AGR-RENTAL-2026-001', $html);
            self::assertStringContainsString('INV-2026-9999', $html);
        });
    }

    public function test_compile_receipt_with_legacy_allocation_missing_snapshot(): void
    {
        [$tenantId, $payment] = $this->paymentFixture(PaymentDirection::Inbound, PaymentType::CustomerReceipt);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $invoiceId = DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payment->organization_unit_id,
                'invoice_number' => 'INV-LEGACY-0042',
                'invoice_type' => 'standard',
                'direction' => 'outbound',
                'party_type' => 'customer',
                'party_id' => $payment->party_id,
                'status' => 'posted',
                'invoice_date' => '2026-10-01',
                'due_date' => '2026-10-15',
                'currency_id' => $payment->currency_id,
                'exchange_rate' => '1.000000',
                'subtotal' => '5000.000000',
                'tax_total' => '0.000000',
                'grand_total' => '5000.000000',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Legacy allocation where snapshot is empty/missing
            DB::table('payment_allocations')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payment->organization_unit_id,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'active_identity_slot' => 1,
                'invoice_number_snapshot' => '',
                'invoice_date_snapshot' => null,
                'invoice_currency_code_snapshot' => 'LKR',
                'invoice_exchange_rate_snapshot' => '1.000000',
                'invoice_total' => '5000.000000',
                'invoice_balance_before' => '5000.000000',
                'previously_allocated_amount' => '0.000000',
                'allocated_amount' => '5000.000000',
                'invoice_balance_after' => '0.000000',
                'allocation_date' => '2026-11-15',
                'allocation_method' => 'specific_invoice',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(PaymentReceiptService::class);
            $receiptData = $service->getReceiptData($payment->fresh());

            // Successfully recovered invoice_number and invoice_date from fallback query
            self::assertCount(1, $receiptData['allocations']);
            self::assertSame('INV-LEGACY-0042', $receiptData['allocations'][0]['invoice_number']);
            self::assertSame('2026-10-01', $receiptData['allocations'][0]['invoice_date']);
        });
    }

    private function paymentFixture(PaymentDirection $direction, PaymentType $type): array
    {
        $code = Str::upper(Str::random(10));
        $currency = CurrencyFixture::create(['name' => 'Currency '.$code, 'code' => 'LKR', 'symbol' => 'Rs']);
        $tenantId = DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Tenant '.$code,
            'slug' => strtolower($code),
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currency,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'code' => $code, 'name' => 'Org '.$code]);
        $user = TenantUserFixture::create(['tenant_id' => $tenantId, 'email' => strtolower($code).'@example.test']);

        $customer = DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => $code,
            'code' => $code,
            'name' => 'Test Customer',
            'customer_type' => 'individual',
            'status' => 'active',
        ]);
        $supplier = DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_number' => $code,
            'code' => $code,
            'name' => 'Test Supplier',
            'supplier_type' => 'individual',
            'status' => 'active',
        ]);

        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $orgId, $currency, $user, $customer, $supplier, $direction, $type): array {
            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'CASH-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => 'both',
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $payment = new Payment;
            $payment->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'payment_number' => 'PAY-'.Str::upper(Str::random(8)),
                'payment_type' => $type->value,
                'direction' => $direction->value,
                'party_type' => $direction === PaymentDirection::Inbound ? 'customer' : 'supplier',
                'party_id' => $direction === PaymentDirection::Inbound ? $customer : $supplier,
                'document_status' => PaymentDocumentStatus::Approved->value,
                'allocation_status' => PaymentAllocationState::Unallocated->value,
                'posting_status' => PaymentPostingStatus::Posted->value,
                'instrument_status' => PaymentInstrumentStatus::Cleared->value,
                'payment_date' => '2026-11-15',
                'currency_id' => $currency,
                'exchange_rate' => '1.000000',
                'reference_number' => 'REF-1002',
                'total_amount' => '15000.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '15000.000000',
                'refunded_amount' => '0.000000',
                'notes' => 'Test payment receipt notes',
                'created_by' => $user,
            ]);
            $payment->save();

            $line = new PaymentLine;
            $line->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'line_number' => 1,
                'payment_method_id' => $method->getKey(),
                'payment_method_code_snapshot' => (string) $method->code,
                'payment_method_name_snapshot' => (string) $method->name,
                'payment_method_type_snapshot' => PaymentMethodType::Cash->value,
                'amount' => '15000.000000',
                'reference_number' => 'CASH-REF',
            ]);
            $line->save();

            return [$tenantId, $payment->fresh(['currency', 'lines.paymentMethod'])];
        });
    }
}
