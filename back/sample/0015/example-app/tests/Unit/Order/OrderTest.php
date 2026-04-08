<?php

namespace Tests\Unit\Order;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Events\OrderWasCancelled;
use App\Domain\Order\Events\OrderWasPlaced;
use App\Domain\Order\Exceptions\OrderException;
use PHPUnit\Framework\TestCase;

/**
 * OrderTest — pure unit tests for the Order aggregate.
 *
 * No database, no Laravel boot, no HTTP — just the domain.
 * These tests run in milliseconds and never touch infrastructure.
 */
class OrderTest extends TestCase
{
    private const CUSTOMER_ID = '550e8400-e29b-41d4-a716-446655440000';

    // -------------------------------------------------------------------------
    // Factory / Creation
    // -------------------------------------------------------------------------

    /** @test */
    public function it_can_be_placed_with_a_customer_id(): void
    {
        $order = Order::place(self::CUSTOMER_ID);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame(self::CUSTOMER_ID, $order->customerId());
        $this->assertSame(OrderStatus::Pending, $order->status());
        $this->assertNotNull($order->id());
        $this->assertNotNull($order->placedAt());
    }

    /** @test */
    public function it_records_an_order_was_placed_event_on_creation(): void
    {
        $order  = Order::place(self::CUSTOMER_ID);
        $events = $order->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderWasPlaced::class, $events[0]);
        $this->assertSame($order->id()->value(), $events[0]->orderId->value());
        $this->assertSame(self::CUSTOMER_ID, $events[0]->customerId);
    }

    /** @test */
    public function pulling_events_clears_the_internal_list(): void
    {
        $order = Order::place(self::CUSTOMER_ID);
        $order->pullDomainEvents(); // first pull

        $this->assertEmpty($order->pullDomainEvents()); // second pull must be empty
    }

    /** @test */
    public function two_orders_placed_independently_have_different_ids(): void
    {
        $a = Order::place(self::CUSTOMER_ID);
        $b = Order::place(self::CUSTOMER_ID);

        $this->assertFalse($a->equals($b));
    }

    /** @test */
    public function it_can_be_reconstituted_from_persisted_state(): void
    {
        $original = Order::place(self::CUSTOMER_ID);

        $reconstituted = Order::reconstitute(
            id:         $original->id()->value(),
            customerId: $original->customerId(),
            status:     $original->status()->value,
            placedAt:   $original->placedAt()->format(\DateTimeInterface::ATOM),
        );

        $this->assertTrue($original->equals($reconstituted));
        $this->assertEmpty($reconstituted->pullDomainEvents()); // no events on reconstitution
    }

    // -------------------------------------------------------------------------
    // Cancellation
    // -------------------------------------------------------------------------

    /** @test */
    public function it_can_be_cancelled_when_pending(): void
    {
        $order = Order::place(self::CUSTOMER_ID);
        $order->pullDomainEvents(); // clear placement event

        $order->cancel('Customer changed their mind.');

        $this->assertSame(OrderStatus::Cancelled, $order->status());
    }

    /** @test */
    public function cancelling_records_an_order_was_cancelled_event(): void
    {
        $order = Order::place(self::CUSTOMER_ID);
        $order->pullDomainEvents();

        $order->cancel('Changed mind.');
        $events = $order->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderWasCancelled::class, $events[0]);
        $this->assertSame('Changed mind.', $events[0]->reason);
    }

    /** @test */
    public function it_throws_when_cancelling_an_already_cancelled_order(): void
    {
        $order = Order::place(self::CUSTOMER_ID);
        $order->cancel('First cancellation.');

        $this->expectException(OrderException::class);
        $this->expectExceptionMessageMatches('/already cancelled/i');

        $order->cancel('Second cancellation attempt.');
    }

    /** @test */
    public function it_throws_when_cancelling_a_shipped_order(): void
    {
        $order = Order::place(self::CUSTOMER_ID);
        $order->markPaid();
        $order->ship();

        $this->expectException(OrderException::class);
        $this->expectExceptionMessageMatches('/shipped/i');

        $order->cancel('Too late.');
    }

    // -------------------------------------------------------------------------
    // Payment
    // -------------------------------------------------------------------------

    /** @test */
    public function it_can_be_marked_paid_when_pending(): void
    {
        $order = Order::place(self::CUSTOMER_ID);
        $order->markPaid();

        $this->assertSame(OrderStatus::Paid, $order->status());
    }

    /** @test */
    public function it_throws_when_marking_a_non_pending_order_as_paid(): void
    {
        $order = Order::place(self::CUSTOMER_ID);
        $order->cancel('oops');

        $this->expectException(OrderException::class);
        $order->markPaid();
    }

    // -------------------------------------------------------------------------
    // Shipping
    // -------------------------------------------------------------------------

    /** @test */
    public function it_can_be_shipped_when_paid(): void
    {
        $order = Order::place(self::CUSTOMER_ID);
        $order->markPaid();
        $order->ship();

        $this->assertSame(OrderStatus::Shipped, $order->status());
    }

    /** @test */
    public function it_throws_when_shipping_before_payment(): void
    {
        $order = Order::place(self::CUSTOMER_ID);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessageMatches('/paid/i');

        $order->ship();
    }

    // -------------------------------------------------------------------------
    // Identity & Equality
    // -------------------------------------------------------------------------

    /** @test */
    public function two_orders_with_the_same_id_are_equal(): void
    {
        $original      = Order::place(self::CUSTOMER_ID);
        $reconstituted = Order::reconstitute(
            $original->id()->value(),
            $original->customerId(),
            $original->status()->value,
            $original->placedAt()->format(\DateTimeInterface::ATOM),
        );

        $this->assertTrue($original->equals($reconstituted));
    }
}
