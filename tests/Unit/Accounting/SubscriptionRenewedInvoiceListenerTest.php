<?php

namespace Tests\Unit\Accounting;

use Mockery;
use Modules\Accounting\Application\Listeners\HandleSubscriptionRenewedListener;
use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionRenewed;
use PHPUnit\Framework\TestCase;


class SubscriptionRenewedInvoiceListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string $subscriptionId    = 'sub-1',
        string $tenantId          = 'tenant-1',
        string $subscriberId      = 'cust-1',
        string $planName          = 'Pro Monthly',
        string $amount            = '49.99',
        string $currency          = 'USD',
        string $currentPeriodStart = '2026-03-01 00:00:00',
        string $currentPeriodEnd   = '2026-04-01 00:00:00',
    ): SubscriptionRenewed {
        return new SubscriptionRenewed(
            subscriptionId:    $subscriptionId,
            tenantId:          $tenantId,
            subscriberId:      $subscriberId,
            planName:          $planName,
            amount:            $amount,
            currency:          $currency,
            currentPeriodStart: $currentPeriodStart,
            currentPeriodEnd:   $currentPeriodEnd,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(tenantId: '');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when amount is zero
    // -------------------------------------------------------------------------

    public function test_skips_when_amount_is_zero(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(amount: '0');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when amount is negative
    // -------------------------------------------------------------------------

    public function test_skips_when_amount_is_negative(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(amount: '-10.00');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: creates AR invoice with correct invoice type and partner type
    // -------------------------------------------------------------------------

    public function test_creates_ar_invoice_for_subscription_renewal(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['invoice_type'] === 'invoice'
                    && $data['partner_type'] === 'customer'
                    && $data['tenant_id']    === 'tenant-1';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent();

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: subscriber ID is set as partner_id
    // -------------------------------------------------------------------------

    public function test_subscriber_id_is_set_as_partner_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['partner_id'] === 'cust-42';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(subscriberId: 'cust-42');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: partner_id is null when subscriberId is empty
    // -------------------------------------------------------------------------

    public function test_partner_id_is_null_when_subscriber_id_empty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['partner_id'] === null;
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(subscriberId: '');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: invoice line has amount equal to subscription amount
    // -------------------------------------------------------------------------

    public function test_invoice_line_has_correct_amount(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $lines = $data['lines'] ?? [];

                return count($lines) === 1
                    && $lines[0]['quantity']   === '1'
                    && $lines[0]['unit_price'] === '99.00'
                    && $lines[0]['tax_rate']   === '0';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(amount: '99.00');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: notes reference subscription ID
    // -------------------------------------------------------------------------

    public function test_notes_reference_subscription_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return str_contains($data['notes'] ?? '', 'sub-999');
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(subscriptionId: 'sub-999');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: currency is passed from event
    // -------------------------------------------------------------------------

    public function test_currency_is_passed_from_event(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['currency'] === 'EUR';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(currency: 'EUR');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Empty currency: defaults to USD
    // -------------------------------------------------------------------------

    public function test_currency_defaults_to_usd_when_empty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['currency'] === 'USD';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(currency: '');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: plan name is included in line description when provided
    // -------------------------------------------------------------------------

    public function test_plan_name_appears_in_line_description(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $desc = $data['lines'][0]['description'] ?? '';

                return str_contains($desc, 'Enterprise Annual');
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(planName: 'Enterprise Annual');

        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: CreateInvoiceUseCase throws
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_create_invoice_throws(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('Accounting DB error'));

        $event = $this->makeEvent();

        // Must not throw — graceful degradation.
        (new HandleSubscriptionRenewedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility: event with only subscriptionId (old callers)
    // -------------------------------------------------------------------------

    public function test_event_defaults_when_optional_fields_not_provided(): void
    {
        $event = new SubscriptionRenewed(subscriptionId: 'sub-legacy');

        $this->assertSame('sub-legacy', $event->subscriptionId);
        $this->assertSame('', $event->tenantId);
        $this->assertSame('', $event->subscriberId);
        $this->assertSame('', $event->planName);
        $this->assertSame('0', $event->amount);
        $this->assertSame('USD', $event->currency);
        $this->assertSame('', $event->currentPeriodStart);
        $this->assertSame('', $event->currentPeriodEnd);
    }

    // -------------------------------------------------------------------------
    // Event carries enriched fields when provided
    // -------------------------------------------------------------------------

    public function test_event_carries_enriched_fields(): void
    {
        $event = $this->makeEvent(
            subscriberId:      'cust-77',
            planName:          'Starter',
            amount:            '9.99',
            currency:          'GBP',
            currentPeriodStart: '2026-04-01 00:00:00',
            currentPeriodEnd:   '2026-05-01 00:00:00',
        );

        $this->assertSame('cust-77', $event->subscriberId);
        $this->assertSame('Starter', $event->planName);
        $this->assertSame('9.99', $event->amount);
        $this->assertSame('GBP', $event->currency);
        $this->assertSame('2026-04-01 00:00:00', $event->currentPeriodStart);
        $this->assertSame('2026-05-01 00:00:00', $event->currentPeriodEnd);
    }
}
