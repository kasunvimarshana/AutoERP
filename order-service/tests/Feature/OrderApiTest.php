<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Saga\SagaOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the SagaOrchestrator to avoid RabbitMQ connections in feature tests
        $this->mock(SagaOrchestrator::class, function ($mock) {
            $mock->shouldReceive('startSaga')->andReturn(Uuid::uuid4()->toString());
            $mock->shouldReceive('compensate')->andReturn(null);
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function validOrderPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id'    => Uuid::uuid4()->toString(),
            'customer_email' => 'customer@example.com',
            'items'          => [
                [
                    'product_id' => Uuid::uuid4()->toString(),
                    'quantity'   => 2,
                    'price'      => 49.99,
                ],
            ],
        ], $overrides);
    }

    public function testCreateOrderReturns201(): void
    {
        $response = $this->postJson('/api/orders', $this->validOrderPayload());

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => ['order_id', 'saga_id', 'status'],
                 ]);

        $this->assertEquals('processing', $response->json('data.status'));
    }

    public function testGetOrderReturns200(): void
    {
        $order = Order::create([
            'customer_id'    => Uuid::uuid4()->toString(),
            'customer_email' => 'view@example.com',
            'items'          => [['product_id' => Uuid::uuid4()->toString(), 'quantity' => 1, 'price' => 10.00]],
            'total_amount'   => 10.00,
            'status'         => Order::STATUS_PENDING,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => ['id', 'customer_id', 'customer_email', 'items', 'total_amount', 'status'],
                 ])
                 ->assertJsonPath('data.id', $order->id);
    }

    public function testGetNonExistentOrderReturns404(): void
    {
        $this->getJson('/api/orders/' . Uuid::uuid4()->toString())
             ->assertStatus(404)
             ->assertJsonStructure(['message']);
    }

    public function testCreateOrderWithInvalidDataReturns422(): void
    {
        $this->postJson('/api/orders', [])
             ->assertStatus(422)
             ->assertJsonStructure(['message', 'errors']);

        $this->postJson('/api/orders', [
            'customer_id'    => 'not-a-uuid',
            'customer_email' => 'bad-email',
            'items'          => [],
        ])->assertStatus(422);
    }
}
