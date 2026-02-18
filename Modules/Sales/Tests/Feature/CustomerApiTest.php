<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\SalesOrder;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'permissions' => [
                'customer.view',
                'customer.create',
                'customer.update',
                'customer.delete',
            ],
        ]);
    }

    public function test_it_lists_customers(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_number',
                        'name',
                        'email',
                        'phone',
                        'type',
                        'status',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_it_creates_customer(): void
    {
        $data = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'type' => 'business',
            'status' => 'active',
            'credit_limit' => 10000,
            'payment_terms' => 'net_30',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/customers', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Customer')
            ->assertJsonPath('data.email', 'test@example.com');

        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
    }

    public function test_it_shows_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sales/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.name', $customer->name);
    }

    public function test_it_updates_customer(): void
    {
        $customer = Customer::factory()->create();

        $data = [
            'name' => 'Updated Customer Name',
            'phone' => '+9876543210',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/customers/{$customer->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Customer Name');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer Name',
            'phone' => '+9876543210',
        ]);
    }

    public function test_it_deletes_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/sales/customers/{$customer->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_it_filters_active_customers(): void
    {
        Customer::factory()->create(['status' => 'active']);
        Customer::factory()->create(['status' => 'inactive']);
        Customer::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/customers?filter[status]=active');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_it_filters_business_customers(): void
    {
        Customer::factory()->create(['type' => 'business']);
        Customer::factory()->create(['type' => 'individual']);
        Customer::factory()->create(['type' => 'business']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/customers?filter[type]=business');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_it_searches_customers_by_name(): void
    {
        Customer::factory()->create(['name' => 'Acme Corporation']);
        Customer::factory()->create(['name' => 'Tech Solutions Inc']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/customers?search=Acme');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Acme Corporation', $data[0]['name']);
    }

    public function test_unauthorized_user_cannot_create_customer(): void
    {
        $unauthorizedUser = User::factory()->create([
            'permissions' => [],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->postJson('/api/v1/sales/customers', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'type' => 'business',
            ]);

        $response->assertStatus(403);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/customers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'type']);
    }

    public function test_it_validates_email_uniqueness(): void
    {
        Customer::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/customers', [
                'name' => 'Test',
                'email' => 'existing@example.com',
                'type' => 'business',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_includes_sales_orders_when_requested(): void
    {
        $customer = Customer::factory()->create();
        SalesOrder::factory()->count(2)->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sales/customers/{$customer->id}?include=salesOrders");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'salesOrders' => [
                        '*' => ['id', 'order_number'],
                    ],
                ],
            ]);
    }
}
