<?php

namespace Tests\Unit;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function testOrderCanBeCreated(): void
    {
        $order = Order::create([
            'customer_id'    => Uuid::uuid4()->toString(),
            'customer_email' => 'test@example.com',
            'items'          => [['product_id' => Uuid::uuid4()->toString(), 'quantity' => 1, 'price' => 99.99]],
            'total_amount'   => 99.99,
            'status'         => Order::STATUS_PENDING,
        ]);

        $this->assertNotNull($order->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $order->id
        );
        $this->assertEquals(Order::STATUS_PENDING, $order->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function testOrderStatusTransitions(): void
    {
        $order = Order::create([
            'customer_id'    => Uuid::uuid4()->toString(),
            'customer_email' => 'test@example.com',
            'items'          => [['product_id' => Uuid::uuid4()->toString(), 'quantity' => 1, 'price' => 10.00]],
            'total_amount'   => 10.00,
            'status'         => Order::STATUS_PENDING,
        ]);

        $order->update(['status' => Order::STATUS_PROCESSING]);
        $this->assertEquals(Order::STATUS_PROCESSING, $order->fresh()->status);

        $order->update(['status' => Order::STATUS_CONFIRMED]);
        $this->assertEquals(Order::STATUS_CONFIRMED, $order->fresh()->status);
    }

    public function testOrderItemsAreCastToArray(): void
    {
        $items = [
            ['product_id' => Uuid::uuid4()->toString(), 'quantity' => 2, 'price' => 25.00],
            ['product_id' => Uuid::uuid4()->toString(), 'quantity' => 1, 'price' => 50.00],
        ];

        $order = Order::create([
            'customer_id'    => Uuid::uuid4()->toString(),
            'customer_email' => 'test@example.com',
            'items'          => $items,
            'total_amount'   => 100.00,
            'status'         => Order::STATUS_PENDING,
        ]);

        $fresh = $order->fresh();
        $this->assertIsArray($fresh->items);
        $this->assertCount(2, $fresh->items);
        $this->assertEquals(2, $fresh->items[0]['quantity']);
    }

    public function testOrderCalculatesTotalAmount(): void
    {
        $items = [
            ['product_id' => Uuid::uuid4()->toString(), 'quantity' => 3, 'price' => 15.00],
            ['product_id' => Uuid::uuid4()->toString(), 'quantity' => 1, 'price' => 45.50],
        ];

        $total = collect($items)->sum(fn ($i) => $i['price'] * $i['quantity']);

        $order = Order::create([
            'customer_id'    => Uuid::uuid4()->toString(),
            'customer_email' => 'total@example.com',
            'items'          => $items,
            'total_amount'   => $total,
            'status'         => Order::STATUS_PENDING,
        ]);

        $this->assertEquals('90.50', $order->fresh()->total_amount);
    }

    public function testOrderHasSagaId(): void
    {
        $sagaId = Uuid::uuid4()->toString();

        $order = Order::create([
            'customer_id'    => Uuid::uuid4()->toString(),
            'customer_email' => 'saga@example.com',
            'items'          => [['product_id' => Uuid::uuid4()->toString(), 'quantity' => 1, 'price' => 5.00]],
            'total_amount'   => 5.00,
            'status'         => Order::STATUS_PROCESSING,
            'saga_id'        => $sagaId,
            'saga_state'     => 'STARTED',
        ]);

        $this->assertEquals($sagaId, $order->fresh()->saga_id);
        $this->assertEquals('STARTED', $order->fresh()->saga_state);
    }
}
