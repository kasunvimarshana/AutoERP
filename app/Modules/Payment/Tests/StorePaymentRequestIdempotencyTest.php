<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Modules\Payment\Constants\PaymentIdempotency;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Http\Requests\StorePaymentRequest;
use Tests\TestCase;

final class StorePaymentRequestIdempotencyTest extends TestCase
{
    private const VALID_KEY = 'payment-request-idempotency-test';

    public function test_request_normalizes_and_accepts_a_non_empty_idempotency_header(): void
    {
        $request = $this->request('  '.self::VALID_KEY.'  ');

        $request->validateResolved();

        self::assertSame(self::VALID_KEY, $request->toData()->idempotencyKey);
    }

    public function test_request_rejects_a_whitespace_only_idempotency_header(): void
    {
        $request = $this->request('   ');

        try {
            $request->validateResolved();
            self::fail('Expected whitespace-only idempotency header validation to fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey(PaymentIdempotency::REQUEST_ATTRIBUTE, $exception->errors());
        }
    }

    public function test_request_rejects_the_retired_rental_receipt_type(): void
    {
        $request = $this->request(self::VALID_KEY, PaymentType::RentalReceipt->value);

        try {
            $request->validateResolved();
            self::fail('Expected the retired rental receipt type to be rejected.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('payment_type', $exception->errors());
        }
    }

    private function request(
        string $idempotencyKey,
        string $paymentType = PaymentType::CustomerReceipt->value,
    ): StorePaymentRequest
    {
        $request = StorePaymentRequest::create(
            '/api/v1/payments',
            'POST',
            [
                'payment_type' => $paymentType,
                'direction' => 'inbound',
                'payment_date' => '2026-07-16',
                'party_type' => 'customer',
                'party_id' => 10,
                'lines' => [[
                    'payment_method_id' => 20,
                    'amount' => '100.000000',
                ]],
            ],
            server: ['HTTP_IDEMPOTENCY_KEY' => $idempotencyKey],
        );
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->attributes->set(
            (string) config('core.current_tenant.id_attribute', 'current_tenant_id'),
            1,
        );
        $request->attributes->set(
            (string) config('core.current_user.id_attribute', 'current_user_id'),
            1,
        );

        return $request;
    }
}
