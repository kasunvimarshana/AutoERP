<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\User;
use App\Services\InventoryServiceClient;
use App\Services\PassportTokenValidationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        app()->instance('current_tenant_id', $this->tenantId);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'roles' => ['admin'],
            'is_active' => true,
        ]);

        $tokenMock = Mockery::mock(PassportTokenValidationService::class);
        $tokenMock->shouldReceive('validate')->andReturn([
            'active' => true,
            'user_id' => $this->adminUser->id,
            'tenant_id' => $this->tenantId,
            'email' => $this->adminUser->email,
            'name' => $this->adminUser->name,
            'roles' => ['admin'],
            'attributes' => [],
        ]);
        app()->instance(PassportTokenValidationService::class, $tokenMock);

        $inventoryMock = Mockery::mock(InventoryServiceClient::class);
        $inventoryMock->shouldReceive('reserveInventory')->andReturn(['success' => true]);
        $inventoryMock->shouldReceive('releaseInventory')->andReturn(['success' => true]);
        $inventoryMock->shouldReceive('confirmInventory')->andReturn(['success' => true]);
        app()->instance(InventoryServiceClient::class, $inventoryMock);

        $paymentMock = Mockery::mock(PaymentService::class);
        $paymentMock->shouldReceive('charge')->andReturn([
            'success' => true, 'payment_id' => 'pay_test123', 'status' => 'captured', 'amount' => 100.00,
        ]);
        $paymentMock->shouldReceive('refund')->andReturn(['success' => true]);
        app()->instance(PaymentService::class, $paymentMock);
    }

    protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJson(['status' => 'ok', 'service' => 'order-service']);
    }

    public function test_list_orders(): void
    {
        $this->withToken('fake-token')->getJson('/api/v1/orders')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_list_orders_with_pagination(): void
    {
        $this->withToken('fake-token')->getJson('/api/v1/orders?per_page=10')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_orders_without_per_page_returns_all(): void
    {
        Order::create(['tenant_id' => $this->tenantId, 'user_id' => $this->adminUser->id, 'status' => Order::STATUS_PENDING, 'total_amount' => 50.00]);
        Order::create(['tenant_id' => $this->tenantId, 'user_id' => $this->adminUser->id, 'status' => Order::STATUS_COMPLETED, 'total_amount' => 100.00]);

        $response = $this->withToken('fake-token')->getJson('/api/v1/orders')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(2, $response->json('data'));
    }

    public function test_create_order_with_saga_success(): void
    {
        $this->withToken('fake-token')->postJson('/api/v1/orders', [
            'items' => [[
                'product_id' => 1, 'warehouse_id' => 1, 'quantity' => 2,
                'unit_price' => 50.00, 'product_name' => 'Test Product', 'product_sku' => 'TEST-001',
            ]],
            'shipping_address' => ['street' => '123 Main St', 'city' => 'New York', 'country' => 'US'],
        ])->assertStatus(201)->assertJsonStructure(['message', 'data' => ['id', 'status', 'total_amount', 'items']]);

        $this->assertDatabaseHas('orders', ['tenant_id' => $this->tenantId, 'status' => Order::STATUS_COMPLETED]);
    }

    public function test_saga_compensates_on_inventory_failure(): void
    {
        $inventoryMock = Mockery::mock(InventoryServiceClient::class);
        $inventoryMock->shouldReceive('reserveInventory')->andReturn(['success' => false, 'message' => 'Insufficient inventory']);
        $inventoryMock->shouldReceive('releaseInventory')->andReturn(['success' => true]);
        $inventoryMock->shouldReceive('confirmInventory')->andReturn(['success' => true]);
        app()->instance(InventoryServiceClient::class, $inventoryMock);

        $this->withToken('fake-token')->postJson('/api/v1/orders', [
            'items' => [['product_id' => 99, 'warehouse_id' => 1, 'quantity' => 999, 'unit_price' => 10.00]],
        ])->assertStatus(422);

        $this->assertDatabaseHas('orders', ['tenant_id' => $this->tenantId, 'status' => Order::STATUS_CANCELLED]);
    }

    public function test_cancel_pending_order(): void
    {
        $order = Order::create(['tenant_id' => $this->tenantId, 'user_id' => $this->adminUser->id, 'status' => Order::STATUS_PENDING, 'total_amount' => 100.00]);
        $this->withToken('fake-token')->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertStatus(200)->assertJsonFragment(['status' => Order::STATUS_CANCELLED]);
    }

    public function test_get_order_saga_log(): void
    {
        $order = Order::create(['tenant_id' => $this->tenantId, 'user_id' => $this->adminUser->id, 'status' => Order::STATUS_COMPLETED, 'total_amount' => 100.00]);
        $this->withToken('fake-token')->getJson("/api/v1/orders/{$order->id}/saga-log")
            ->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_request_rejected(): void
    {
        $this->getJson('/api/v1/orders')->assertStatus(401);
    }
}
