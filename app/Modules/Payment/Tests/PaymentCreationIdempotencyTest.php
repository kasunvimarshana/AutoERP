<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Tenant\Constants\TenantStatus;
use Tests\TestCase;

final class PaymentCreationIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private const PAYMENT_DATE = '2026-07-16';
    private const IDEMPOTENCY_KEY = 'payment-create-idempotency-test';
    private const PAYMENT_METHOD_CODE = 'IDEMP-CASH';
    private const PAYMENT_METHOD_NAME = 'Idempotency Cash';

    public function test_exact_retry_returns_the_existing_payment_without_duplicate_writes(): void
    {
        [$tenantId, $customerId, $paymentMethodId] = $this->fixtures();
        $data = $this->paymentData($tenantId, $customerId, $paymentMethodId, '100.000000');

        $first = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PaymentCreationService::class)->create($data),
        );
        $retried = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PaymentCreationService::class)->create($data),
        );

        $this->assertSame($first->getKey(), $retried->getKey());
        $this->assertSame(1, DB::table('payments')->where('tenant_id', $tenantId)->count());
        $this->assertSame(1, DB::table('payment_lines')->where('tenant_id', $tenantId)->count());
        $this->assertSame(1, DB::table('idempotency_records')->where('tenant_id', $tenantId)->count());
    }

    public function test_reusing_a_key_for_a_different_payload_is_rejected(): void
    {
        [$tenantId, $customerId, $paymentMethodId] = $this->fixtures();
        $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PaymentCreationService::class)->create(
                $this->paymentData($tenantId, $customerId, $paymentMethodId, '100.000000'),
            ),
        );

        try {
            $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(PaymentCreationService::class)->create(
                    $this->paymentData($tenantId, $customerId, $paymentMethodId, '125.000000'),
                ),
            );
            self::fail('Expected the idempotency payload conflict to be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('different request payload', $exception->getMessage());
        }

        $this->assertSame(1, DB::table('payments')->where('tenant_id', $tenantId)->count());
    }

    public function test_failed_validation_rolls_back_the_idempotency_record_and_allows_a_corrected_retry(): void
    {
        [$tenantId, $customerId, $paymentMethodId] = $this->fixtures();

        try {
            $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(PaymentCreationService::class)->create(
                    $this->paymentData($tenantId, $customerId, $paymentMethodId + 9999, '100.000000'),
                ),
            );
            self::fail('Expected invalid payment-method validation to fail.');
        } catch (InvalidArgumentException) {
            $this->assertSame(0, DB::table('idempotency_records')->where('tenant_id', $tenantId)->count());
        }

        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PaymentCreationService::class)->create(
                $this->paymentData($tenantId, $customerId, $paymentMethodId, '100.000000'),
            ),
        );

        $this->assertNotNull($payment->getKey());
        $this->assertSame(1, DB::table('payments')->where('tenant_id', $tenantId)->count());
        $this->assertSame(1, DB::table('idempotency_records')->where('tenant_id', $tenantId)->count());
    }

    private function fixtures(): array
    {
        $tenantId = $this->createTenant();
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-IDEMP',
            'code' => 'CUS-IDEMP',
            'name' => 'Idempotent Payment Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentMethodId = (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => self::PAYMENT_METHOD_CODE,
            'name' => self::PAYMENT_METHOD_NAME,
            'method_type' => PaymentMethodType::Cash->value,
            'direction_allowed' => PaymentMethodDirection::Both->value,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $customerId, $paymentMethodId];
    }

    private function paymentData(int $tenantId, int $customerId, int $paymentMethodId, string $amount): CreatePaymentData
    {
        return new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: self::PAYMENT_DATE,
            partyType: 'customer',
            partyId: $customerId,
            lines: [new PaymentLineData(
                amount: $amount,
                paymentMethodId: $paymentMethodId,
            )],
            idempotencyKey: self::IDEMPOTENCY_KEY,
        );
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(5));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-IDEMP-'.$suffix,
            'name' => 'Payment Idempotency '.$suffix,
            'slug' => 'payment-idempotency-'.Str::lower($suffix),
            'status' => TenantStatus::ACTIVE,
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
