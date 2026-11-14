<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Tenancy\TenantFeature;
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

            $payment = new Payment();
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

            $line = new PaymentLine();
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
