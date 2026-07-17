<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentRefundPolicyService;
use Tests\TestCase;

final class PaymentRefundPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_and_posting_use_the_same_original_payment_policy(): void
    {
        $tenantId = $this->tenantId();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $original = $this->payment($tenantId, PaymentType::CustomerReceipt, PaymentDirection::Inbound);
            $data = new CreatePaymentData(
                tenantId: $tenantId,
                paymentType: PaymentType::Refund,
                direction: PaymentDirection::Outbound,
                paymentDate: '2026-07-16',
                partyType: 'customer',
                partyId: 15,
                sourceType: PaymentSourceType::PaymentRefund->value,
                sourceId: (int) $original->getKey(),
                originalPaymentId: (int) $original->getKey(),
            );
            $refund = $this->unsavedRefund($tenantId, $original, currencyId: null);

            $policy = app(PaymentRefundPolicyService::class);

            self::assertSame($original->getKey(), $policy->originalForCreation($data)->getKey());
            self::assertSame($original->getKey(), $policy->originalForPayment($refund)->getKey());
        });
    }

    public function test_posting_policy_rejects_currency_drift_from_the_original_payment(): void
    {
        $tenantId = $this->tenantId();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $original = $this->payment($tenantId, PaymentType::CustomerReceipt, PaymentDirection::Inbound);
            $refund = $this->unsavedRefund($tenantId, $original, currencyId: 999);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Refund payment scope, party, and currency must match the original payment.');

            app(PaymentRefundPolicyService::class)->originalForPayment($refund);
        });
    }

    private function payment(
        int $tenantId,
        PaymentType $type,
        PaymentDirection $direction,
    ): Payment {
        $payment = new Payment();
        $payment->forceFill([
            'tenant_id' => $tenantId,
            'payment_number' => 'PAY-'.Str::upper(Str::random(8)),
            'payment_type' => $type->value,
            'direction' => $direction->value,
            'party_type' => 'customer',
            'party_id' => 15,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'allocation_status' => PaymentAllocationState::Unallocated->value,
            'posting_status' => PaymentPostingStatus::Posted->value,
            'instrument_status' => PaymentInstrumentStatus::Cleared->value,
            'payment_date' => '2026-07-15',
            'exchange_rate' => '1.000000',
            'total_amount' => '100.000000',
            'allocated_amount' => '0.000000',
            'unapplied_amount' => '100.000000',
            'refunded_amount' => '0.000000',
        ]);
        $payment->save();

        return $payment;
    }

    private function unsavedRefund(int $tenantId, Payment $original, ?int $currencyId): Payment
    {
        $refund = new Payment();
        $refund->forceFill([
            'tenant_id' => $tenantId,
            'payment_type' => PaymentType::Refund->value,
            'direction' => PaymentDirection::Outbound->value,
            'party_type' => 'customer',
            'party_id' => 15,
            'currency_id' => $currencyId,
            'original_payment_id' => $original->getKey(),
        ]);

        return $refund;
    }

    private function tenantId(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-REF-'.$suffix,
            'name' => 'Refund Policy '.$suffix,
            'slug' => 'refund-policy-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
