<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Saga\OrderSagaOrchestrator;
use App\Services\RabbitMQService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TENANT_HEADERS = ['X-Tenant-ID' => '1'];

    protected function setUp(): void
    {
        parent::setUp();

        // Stub RabbitMQ so tests don't require a live broker.
        $this->app->instance(RabbitMQService::class, Mockery::mock(RabbitMQService::class, function ($mock) {
            $mock->shouldReceive('publishCommand')->zeroOrMoreTimes()->andReturn();
            $mock->shouldReceive('publishEvent')->zeroOrMoreTimes()->andReturn();
        }));

        Redis::shouldReceive('setex')->zeroOrMoreTimes()->andReturn(true);
        Redis::shouldReceive('get')->zeroOrMoreTimes()->andReturn(null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // GET /api/health
    // =========================================================================

    /** @test */
    public function health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
             ->assertOk()
             ->assertJson(['status' => 'ok', 'service' => 'order-service']);
    }

    // =========================================================================
    // GET /api/v1/orders
    // =========================================================================

    /** @test */
    public function index_returns_paginated_order_list(): void
    {
        Order::factory(3)->create(['tenant_id' => 1]);
        Order::factory(2)->create(['tenant_id' => 2]);

        $response = $this->getJson('/api/v1/orders', self::TENANT_HEADERS);

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

        // Only tenant 1's orders are returned.
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function index_returns_empty_list_when_no_orders(): void
    {
        $response = $this->getJson('/api/v1/orders', self::TENANT_HEADERS);

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    // =========================================================================
    // POST /api/v1/orders
    // =========================================================================

    /** @test */
    public function store_creates_order_and_starts_saga(): void
    {
        $payload = $this->sampleOrderPayload();

        $response = $this->postJson('/api/v1/orders', $payload, self::TENANT_HEADERS);

        $response->assertStatus(202);
        $response->assertJsonStructure([
            'message',
            'data'    => ['id', 'status', 'saga_id', 'total_amount'],
            'saga_id',
        ]);
        $response->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'tenant_id'    => 1,
            'customer_id'  => $payload['customer_id'],
            'status'       => 'pending',
        ]);
    }

    /** @test */
    public function store_returns_422_when_items_are_missing(): void
    {
        $payload = $this->sampleOrderPayload();
        unset($payload['items']);

        $this->postJson('/api/v1/orders', $payload, self::TENANT_HEADERS)
             ->assertUnprocessable()
             ->assertJsonStructure(['message', 'errors' => ['items']]);
    }

    /** @test */
    public function store_returns_422_when_total_amount_is_missing(): void
    {
        $payload = $this->sampleOrderPayload();
        unset($payload['total_amount']);

        $this->postJson('/api/v1/orders', $payload, self::TENANT_HEADERS)
             ->assertUnprocessable()
             ->assertJsonStructure(['errors' => ['total_amount']]);
    }

    // =========================================================================
    // GET /api/v1/orders/{id}
    // =========================================================================

    /** @test */
    public function show_returns_order_with_saga_transactions(): void
    {
        $order = Order::factory()->create(['tenant_id' => 1]);

        $response = $this->getJson("/api/v1/orders/{$order->id}", self::TENANT_HEADERS);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['id', 'status', 'saga_transactions'],
        ]);
    }

    /** @test */
    public function show_returns_404_for_unknown_order(): void
    {
        $this->getJson('/api/v1/orders/999', self::TENANT_HEADERS)->assertNotFound();
    }

    /** @test */
    public function show_returns_404_for_another_tenants_order(): void
    {
        $order = Order::factory()->create(['tenant_id' => 2]);

        $this->getJson("/api/v1/orders/{$order->id}", self::TENANT_HEADERS)->assertNotFound();
    }

    // =========================================================================
    // DELETE /api/v1/orders/{id}
    // =========================================================================

    /** @test */
    public function cancel_returns_422_when_order_is_not_pending(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => 1,
            'status'    => Order::STATUS_CONFIRMED,
        ]);

        $this->deleteJson("/api/v1/orders/{$order->id}", [], self::TENANT_HEADERS)
             ->assertUnprocessable();
    }

    /** @test */
    public function cancel_returns_404_for_unknown_order(): void
    {
        $this->deleteJson('/api/v1/orders/999', [], self::TENANT_HEADERS)->assertNotFound();
    }

    /** @test */
    public function cancel_initiates_saga_compensation_for_pending_order(): void
    {
        $sagaId = (string) \Illuminate\Support\Str::uuid();

        $order = Order::factory()->create([
            'tenant_id' => 1,
            'status'    => Order::STATUS_PENDING,
            'saga_id'   => $sagaId,
        ]);

        // Seed the saga state so handleStepFailure has something to work with.
        $state = [
            'saga_id'         => $sagaId,
            'order_id'        => $order->id,
            'status'          => 'in_progress',
            'current_step'    => 'RESERVE_INVENTORY',
            'completed_steps' => ['CREATE_ORDER'],
            'step_results'    => ['CREATE_ORDER' => ['order_id' => $order->id]],
            'order_data'      => $this->sampleOrderPayload(),
        ];

        Redis::shouldReceive('get')
            ->with("saga:{$sagaId}")
            ->andReturn(json_encode($state));

        $this->deleteJson("/api/v1/orders/{$order->id}", [], self::TENANT_HEADERS)
             ->assertOk()
             ->assertJsonStructure(['message']);
    }

    // =========================================================================
    // GET /api/v1/orders/{id}/saga-status
    // =========================================================================

    /** @test */
    public function saga_status_returns_redis_state_when_available(): void
    {
        $sagaId = (string) \Illuminate\Support\Str::uuid();

        $order = Order::factory()->create([
            'tenant_id' => 1,
            'saga_id'   => $sagaId,
        ]);

        $state = [
            'saga_id'  => $sagaId,
            'order_id' => $order->id,
            'status'   => 'in_progress',
        ];

        Redis::shouldReceive('get')
            ->with("saga:{$sagaId}")
            ->andReturn(json_encode($state));

        $response = $this->getJson("/api/v1/orders/{$order->id}/saga-status", self::TENANT_HEADERS);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'in_progress');
        $response->assertJsonPath('data.source', 'redis');
    }

    /** @test */
    public function saga_status_falls_back_to_database_when_redis_is_empty(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => 1,
            'status'    => Order::STATUS_PENDING,
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/saga-status", self::TENANT_HEADERS);

        $response->assertOk();
        $response->assertJsonPath('data.source', 'database');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function sampleOrderPayload(): array
    {
        return [
            'customer_id'  => 'cust-test-1',
            'items'        => [
                ['product_id' => 'prod-a', 'quantity' => 1, 'unit_price' => 29.99],
            ],
            'total_amount' => '29.99',
            'currency'     => 'USD',
            'metadata'     => ['channel' => 'web'],
        ];
    }
}
