<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Services\PaymentInstrumentStateResolver;
use PHPUnit\Framework\TestCase;

final class PaymentInstrumentStateResolverTest extends TestCase
{
    private PaymentInstrumentStateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PaymentInstrumentStateResolver();
    }

    public function test_empty_lines_are_pending(): void
    {
        self::assertSame(PaymentInstrumentStatus::Pending, $this->resolver->resolve([]));
    }

    public function test_terminal_states_are_preserved(): void
    {
        self::assertSame(
            PaymentInstrumentStatus::Settled,
            $this->resolver->resolve(['settled', 'settled']),
        );
        self::assertSame(
            PaymentInstrumentStatus::Refunded,
            $this->resolver->resolve(['refunded']),
        );
        self::assertSame(
            PaymentInstrumentStatus::Reversed,
            $this->resolver->resolve(['reversed', 'reversed']),
        );
    }

    public function test_cleared_and_settled_lines_resolve_to_cleared(): void
    {
        self::assertSame(
            PaymentInstrumentStatus::Cleared,
            $this->resolver->resolve(['cleared', 'settled']),
        );
    }

    public function test_risk_states_take_priority_in_mixed_payments(): void
    {
        self::assertSame(
            PaymentInstrumentStatus::Bounced,
            $this->resolver->resolve(['settled', 'bounced']),
        );
        self::assertSame(
            PaymentInstrumentStatus::Failed,
            $this->resolver->resolve(['authorized', 'failed']),
        );
    }

    public function test_payment_creation_contract_excludes_dead_server_owned_fields(): void
    {
        $data = new CreatePaymentData(
            tenantId: 1,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-29',
            lines: [new PaymentLineData(amount: '10.000000', paymentMethodId: 1)],
        );

        self::assertFalse(property_exists($data, 'paymentNumber'));
        self::assertFalse(property_exists($data, 'amountInWords'));
        self::assertSame('pending', $data->lines[0]->status);
        self::assertSame('0.000000', $data->lines[0]->clearedAmount);
    }
}
