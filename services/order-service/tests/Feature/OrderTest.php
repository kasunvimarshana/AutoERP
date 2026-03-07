<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderSagaService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private OrderService    $service;
    private OrderSagaService $sagaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service     = $this->app->make(OrderService::class);
        $this->sagaService = $this->app->make(OrderSagaService::class);

        // Prevent listeners from attempting real RabbitMQ / HTTP calls in tests
        Event::fake([
            OrderCreated::class,
            OrderUpdated::class,
            OrderCancelled::class,
            OrderCompleted::class,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function orderPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id'    => $this->faker->uuid(),
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'shipping_address' => [
                'street'      => '123 Test St',
                'city'        => 'Testville',
                'state'       => 'CA',
                'postal_code' => '90210',
                'country'     => 'USA',
            ],
            'items' => [
                [
                    'product_id'   => 1,
                    'product_name' => 'Test Widget',
                    'product_sku'  => 'SKU-TEST-001',
                    'quantity'     => 2,
                    'unit_price'   => 19.99,
                ],
            ],
        ], $overrides);
    }

    private function mockInventoryReservationSuccess(): void
    {
        Http::fake([
            '*/inventory/product/*/reserve' => Http::response(['message' => 'Reserved'], 200),
            '*/inventory/product/*/release' => Http::response(['message' => 'Released'], 200),
        ]);
    }

    private function mockInventoryReservationFailure(int $statusCode = 422): void
    {
        Http::fake([
            '*/inventory/product/*/reserve' => Http::response(
                ['message' => 'Insufficient stock'],
                $statusCode
            ),
        ]);
    }

    private function makeConfirmedOrder(array $overrides = []): Order
    {
        $order = Order::factory()->confirmed()->create($overrides);

        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => 1,
            'product_name' => 'Widget',
            'quantity'     => 2,
            'unit_price'   => 19.99,
            'total_price'  => 39.98,
            'status'       => OrderItem::STATUS_CONFIRMED,
        ]);

        return $order->fresh(['items']);
    }

    // ── INDEX ────────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_orders(): void
    {
        Order::factory()->count(20)->create();

        $result = $this->service->getAllOrders(['per_page' => 10]);

        $this->assertSame(10, $result->perPage());
        $this->assertSame(20, $result->total());
    }

    public function test_index_filters_by_status(): void
    {
        Order::factory()->count(3)->pending()->create();
        Order::factory()->count(2)->confirmed()->create();

        $result = $this->service->getAllOrders(['status' => 'pending']);

        $this->assertSame(3, $result->total());
        $result->getCollection()->each(
            fn (Order $o) => $this->assertSame('pending', $o->status)
        );
    }

    public function test_index_search_by_customer_name(): void
    {
        Order::factory()->create(['customer_name' => 'Alice Example']);
        Order::factory()->create(['customer_name' => 'Bob Test']);

        $result = $this->service->getAllOrders(['search' => 'Alice']);

        $this->assertSame(1, $result->total());
        $this->assertSame('Alice Example', $result->getCollection()->first()->customer_name);
    }

    public function test_index_sorts_by_total_amount_ascending(): void
    {
        Order::factory()->create(['total_amount' => 300.00]);
        Order::factory()->create(['total_amount' => 100.00]);
        Order::factory()->create(['total_amount' => 200.00]);

        $result = $this->service->getAllOrders([
            'sort_by'        => 'total_amount',
            'sort_direction' => 'asc',
        ]);

        $amounts = $result->getCollection()->pluck('total_amount')->map(fn ($v) => (float) $v)->toArray();

        $this->assertSame([100.0, 200.0, 300.0], $amounts);
    }

    // ── SHOW ─────────────────────────────────────────────────────────────────

    public function test_get_order_by_id_returns_order_with_items(): void
    {
        $order = $this->makeConfirmedOrder();

        $found = $this->service->getOrderById($order->id);

        $this->assertNotNull($found);
        $this->assertSame($order->id, $found->id);
        $this->assertNotNull($found->items);
    }

    public function test_get_order_by_id_returns_null_for_missing_id(): void
    {
        $result = $this->service->getOrderById(99999);

        $this->assertNull($result);
    }

    // ── CREATE ORDER SAGA ─────────────────────────────────────────────────────

    public function test_create_order_saga_succeeds_when_inventory_is_available(): void
    {
        $this->mockInventoryReservationSuccess();

        $order = $this->service->createOrder($this->orderPayload());

        $this->assertDatabaseHas('orders', [
            'id'          => $order->id,
            'status'      => Order::STATUS_CONFIRMED,
            'saga_status' => Order::SAGA_COMPLETED,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id'   => $order->id,
            'product_id' => 1,
            'status'     => OrderItem::STATUS_CONFIRMED,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    public function test_create_order_saga_calculates_totals_correctly(): void
    {
        $this->mockInventoryReservationSuccess();

        $payload = $this->orderPayload([
            'tax_amount'      => 4.00,
            'discount_amount' => 2.00,
            'items'           => [
                ['product_id' => 1, 'product_name' => 'Item A', 'quantity' => 2, 'unit_price' => 10.00],
                ['product_id' => 2, 'product_name' => 'Item B', 'quantity' => 1, 'unit_price' => 5.00],
            ],
        ]);

        $order = $this->service->createOrder($payload);

        // Subtotal = (2*10) + (1*5) = 25; tax = 4; discount = 2; total = 27
        $this->assertSame(27.0, (float) $order->total_amount);
        $this->assertSame(4.0, (float) $order->tax_amount);
        $this->assertSame(2.0, (float) $order->discount_amount);
    }

    public function test_create_order_saga_compensates_when_inventory_is_insufficient(): void
    {
        $this->mockInventoryReservationFailure(422);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/');

        $this->service->createOrder($this->orderPayload());

        // After exception the order should be cancelled
        $this->assertDatabaseHas('orders', [
            'status'      => Order::STATUS_CANCELLED,
            'saga_status' => Order::SAGA_COMPENSATED,
        ]);
    }

    public function test_create_order_saga_compensates_when_inventory_service_unreachable(): void
    {
        Http::fake([
            '*/inventory/product/*/reserve' => Http::response('Service unavailable', 503),
            '*/inventory/product/*/release' => Http::response(['message' => 'Released'], 200),
        ]);

        $this->expectException(RuntimeException::class);

        $this->service->createOrder($this->orderPayload());

        $this->assertDatabaseHas('orders', [
            'status'      => Order::STATUS_CANCELLED,
            'saga_status' => Order::SAGA_COMPENSATED,
        ]);
    }

    // ── CANCEL ORDER SAGA ─────────────────────────────────────────────────────

    public function test_cancel_order_saga_releases_inventory_and_marks_cancelled(): void
    {
        Http::fake([
            '*/inventory/product/*/release' => Http::response(['message' => 'Released'], 200),
        ]);

        $order = Order::factory()->pending()->create([
            'saga_compensation_data' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);

        $cancelled = $this->service->cancelOrder($order->id);

        $this->assertSame(Order::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame(Order::SAGA_COMPENSATED, $cancelled->saga_status);
        $this->assertNotNull($cancelled->cancelled_at);

        Event::assertDispatched(OrderCancelled::class);
    }

    public function test_cancel_order_saga_still_cancels_when_inventory_release_fails(): void
    {
        Http::fake([
            '*/inventory/product/*/release' => Http::response('Unavailable', 503),
        ]);

        $order = Order::factory()->pending()->create([
            'saga_compensation_data' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);

        // Should not throw – the order is cancelled even if inventory release fails
        $cancelled = $this->service->cancelOrder($order->id);

        $this->assertSame(Order::STATUS_CANCELLED, $cancelled->status);

        Event::assertDispatched(OrderCancelled::class);
    }

    public function test_cancel_order_rejects_non_cancellable_status(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SHIPPED]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot be cancelled/');

        $this->service->cancelOrder($order->id);
    }

    public function test_cancel_nonexistent_order_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->service->cancelOrder(99999);
    }

    // ── STATUS TRANSITIONS ────────────────────────────────────────────────────

    public function test_confirm_order_advances_status_when_inventory_reserved(): void
    {
        $order = Order::factory()->create([
            'status'      => Order::STATUS_PENDING,
            'saga_status' => Order::SAGA_INVENTORY_RESERVED,
        ]);

        $confirmed = $this->service->confirmOrder($order->id);

        $this->assertSame(Order::STATUS_CONFIRMED, $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);

        Event::assertDispatched(OrderUpdated::class);
    }

    public function test_confirm_order_rejects_when_saga_not_ready(): void
    {
        $order = Order::factory()->create([
            'status'      => Order::STATUS_PENDING,
            'saga_status' => Order::SAGA_STARTED,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot be confirmed/');

        $this->service->confirmOrder($order->id);
    }

    public function test_ship_order_advances_confirmed_to_shipped(): void
    {
        $order = $this->makeConfirmedOrder();

        $shipped = $this->service->shipOrder($order->id);

        $this->assertSame(Order::STATUS_SHIPPED, $shipped->status);
        $this->assertNotNull($shipped->shipped_at);

        Event::assertDispatched(OrderUpdated::class);
    }

    public function test_ship_order_rejects_when_already_shipped(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SHIPPED]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot be shipped/');

        $this->service->shipOrder($order->id);
    }

    public function test_deliver_order_advances_shipped_to_delivered(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SHIPPED]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $delivered = $this->service->deliverOrder($order->id);

        $this->assertSame(Order::STATUS_DELIVERED, $delivered->status);
        $this->assertNotNull($delivered->delivered_at);

        Event::assertDispatched(OrderCompleted::class);
    }

    public function test_deliver_order_rejects_when_not_shipped(): void
    {
        $order = $this->makeConfirmedOrder();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot be delivered/');

        $this->service->deliverOrder($order->id);
    }

    // ── MY ORDERS ─────────────────────────────────────────────────────────────

    public function test_get_orders_by_customer_returns_only_that_customers_orders(): void
    {
        $customerId = $this->faker->uuid();

        Order::factory()->count(3)->create(['customer_id' => $customerId]);
        Order::factory()->count(2)->create(['customer_id' => $this->faker->uuid()]);

        $result = $this->service->getOrdersByCustomer($customerId);

        $this->assertSame(3, $result->total());
        $result->getCollection()->each(
            fn (Order $o) => $this->assertSame($customerId, $o->customer_id)
        );
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    public function test_delete_order_succeeds_when_cancelled(): void
    {
        $order = Order::factory()->cancelled()->create();

        $deleted = $this->service->deleteOrder($order->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_delete_order_rejects_when_not_cancelled(): void
    {
        $order = $this->makeConfirmedOrder();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/must be cancelled/');

        $this->service->deleteOrder($order->id);
    }

    // ── ORDER NUMBER ──────────────────────────────────────────────────────────

    public function test_order_number_is_auto_generated_on_create(): void
    {
        $this->mockInventoryReservationSuccess();

        $order = $this->service->createOrder($this->orderPayload());

        $this->assertNotEmpty($order->order_number);
        $this->assertStringStartsWith('ORD-', $order->order_number);
    }

    public function test_order_numbers_are_unique(): void
    {
        $this->mockInventoryReservationSuccess();

        $order1 = $this->service->createOrder($this->orderPayload());
        $order2 = $this->service->createOrder($this->orderPayload());

        $this->assertNotSame($order1->order_number, $order2->order_number);
    }
}
