<?php

namespace Tests\Feature\Api\V1;

use App\Models\Inventory;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PassportTokenValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private int $tenantId = 1;
    private Warehouse $warehouse;

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

        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Test Warehouse',
            'code' => 'TEST-WH',
            'city' => 'Test City',
            'country' => 'US',
            'is_active' => true,
        ]);

        $mock = Mockery::mock(PassportTokenValidationService::class);
        $mock->shouldReceive('validate')->andReturn([
            'active' => true,
            'user_id' => $this->adminUser->id,
            'tenant_id' => $this->tenantId,
            'email' => $this->adminUser->email,
            'name' => $this->adminUser->name,
            'roles' => ['admin'],
            'attributes' => [],
        ]);
        app()->instance(PassportTokenValidationService::class, $mock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJson(['status' => 'ok', 'service' => 'inventory-service']);
    }

    public function test_list_inventory(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/inventory')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_list_inventory_with_pagination(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/inventory?per_page=10')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_inventory_without_per_page_returns_all(): void
    {
        Inventory::create([
            'tenant_id' => $this->tenantId,
            'product_id' => 10,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'reserved_quantity' => 0,
            'reorder_level' => 2,
        ]);

        $response = $this->withToken('fake-token')
            ->getJson('/api/v1/inventory')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(1, $response->json('data'));
    }

    public function test_create_inventory(): void
    {
        $this->withToken('fake-token')
            ->postJson('/api/v1/inventory', [
                'product_id' => 1,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 100,
                'reorder_level' => 10,
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'product_id', 'quantity', 'available_quantity'],
            ]);
    }

    public function test_adjust_inventory(): void
    {
        $inventory = Inventory::create([
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
            'reorder_level' => 10,
        ]);

        $this->withToken('fake-token')
            ->patchJson("/api/v1/inventory/{$inventory->id}/adjust", [
                'quantity' => 20,
                'reason' => 'Received shipment',
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['quantity' => 70]);
    }

    public function test_reserve_inventory_requires_secret(): void
    {
        $this->postJson('/api/v1/inventory/reserve', [
            'product_id' => 1,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
        ])->assertStatus(401);
    }

    public function test_list_warehouses(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/warehouses')
            ->assertStatus(200);
    }

    public function test_list_warehouses_with_pagination(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/warehouses?per_page=5')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_warehouses_without_per_page_returns_all(): void
    {
        Warehouse::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Second Warehouse',
            'code' => 'SEC-WH',
            'city' => 'Other City',
            'country' => 'US',
            'is_active' => true,
        ]);

        $response = $this->withToken('fake-token')
            ->getJson('/api/v1/warehouses')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(2, $response->json('data'));
    }
}
