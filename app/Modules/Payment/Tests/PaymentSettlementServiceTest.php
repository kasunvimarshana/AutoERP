<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Payment\Constants\PaymentPostingMetadata;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingRole;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentSettlementService;
use Tests\TestCase;

final class PaymentSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlement_records_explicit_business_event_date_and_versions(): void
    {
        [$tenantId, $payment] = $this->postedBankTransfer();

        $this->withTenantExecutionContext($tenantId, function () use ($payment): void {
            $line = $payment->lines()->firstOrFail();

            $settled = app(PaymentSettlementService::class)->transitionLine(
                $payment->refresh(),
                (int) $line->getKey(),
                'settled',
                '2026-06-08',
                1,
                1,
                reason: 'Bank confirmation received.',
            );

            $this->assertSame('settled', (string) $settled->status);
            $this->assertSame('100.000000', (string) $settled->cleared_amount);
            $this->assertSame('2026-06-08', $settled->clearing_date?->toDateString());
            $this->assertSame('2026-06-08', $settled->realized_date?->toDateString());
            $this->assertSame(2, (int) $settled->row_version);

            $refreshed = $payment->refresh();
            $this->assertSame(PaymentInstrumentStatus::Settled, $refreshed->instrument_status);
            $this->assertSame(2, (int) $refreshed->row_version);
        });
    }

    public function test_settlement_rejects_event_date_before_payment_without_mutation(): void
    {
        [$tenantId, $payment] = $this->postedBankTransfer();

        $this->withTenantExecutionContext($tenantId, function () use ($payment): void {
            $line = $payment->lines()->firstOrFail();

            try {
                app(PaymentSettlementService::class)->transitionLine(
                    $payment->refresh(),
                    (int) $line->getKey(),
                    'settled',
                    '2026-06-05',
                    1,
                    1,
                );
                self::fail('A settlement before the payment date was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Payment settlement event date cannot be before the payment date.',
                    $exception->getMessage(),
                );
            }

            $line->refresh();
            $payment->refresh();
            $this->assertSame('pending', (string) $line->status);
            $this->assertNull($line->clearing_date);
            $this->assertNull($line->realized_date);
            $this->assertSame(1, (int) $line->row_version);
            $this->assertSame(1, (int) $payment->row_version);
        });
    }

    /** @return array{0:int,1:Payment} */
    private function postedBankTransfer(): array
    {
        $tenantId = $this->tenant();

        $payment = $this->withTenantExecutionContext($tenantId, function () use ($tenantId): Payment {
            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'BANK',
                'name' => 'Bank Transfer',
                'method_type' => PaymentMethodType::BankTransfer,
                'direction_allowed' => 'both',
                'requires_reference' => false,
                'requires_instrument_details' => false,
                'is_active' => true,
            ]);

            $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
                tenantId: $tenantId,
                paymentType: PaymentType::Manual,
                direction: PaymentDirection::Inbound,
                paymentDate: '2026-06-06',
                lines: [
                    new PaymentLineData(
                        amount: '100.000000',
                        paymentMethodId: (int) $method->getKey(),
                        status: 'pending',
                        instrumentDirection: 'received',
                    ),
                ],
                metadata: [
                    PaymentPostingMetadata::PROFILE_CODE => 'manual_payment_test',
                    PaymentPostingMetadata::COUNTERPARTY_ROLE => PaymentPostingRole::Payable->value,
                ],
            ));

            $payment->forceFill([
                'document_status' => PaymentDocumentStatus::Approved->value,
                'posting_status' => PaymentPostingStatus::Posted->value,
            ])->save();

            return $payment->refresh();
        });

        return [$tenantId, $payment];
    }

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-PSET-'.$suffix,
            'name' => 'Payment Settlement '.$suffix,
            'slug' => 'payment-settlement-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
