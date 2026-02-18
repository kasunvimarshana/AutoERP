<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\CRM\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRM Customer API Tests
 *
 * Tests the customer management endpoints for the CRM module
 */
class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::factory()->create();

        // Create a user for the tenant
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_list_customers(): void
    {
        // Create some customers
        Customer::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crm/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                            'customer_type',
                            'is_active',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_can_create_customer(): void
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'customer_type' => 'individual',
            'credit_limit' => 10000,
            'payment_terms_days' => 30,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crm/customers', $customerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'customer_type',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_cannot_create_customer_with_duplicate_email(): void
    {
        // Create an existing customer
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'duplicate@example.com',
        ]);

        $customerData = [
            'name' => 'Jane Doe',
            'email' => 'duplicate@example.com',
            'customer_type' => 'individual',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crm/customers', $customerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_get_customer_by_id(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crm/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                ],
            ]);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Old Name',
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
            'credit_limit' => 20000,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/crm/customers/{$customer->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New Name',
                    'email' => 'newemail@example.com',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/crm/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_tenant_isolation_prevents_accessing_other_tenant_customers(): void
    {
        // Create another tenant and customer
        $otherTenant = Tenant::factory()->create();
        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/crm/customers/{$otherCustomer->id}");

        $response->assertStatus(404);
    }

    public function test_can_search_customers(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Smith',
        ]);

        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jane Doe',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crm/customers/search?q=John');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data.data') ?? $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('John Smith', $data[0]['name']);
    }

    public function test_validates_required_fields_on_create(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crm/customers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'customer_type']);
    }

    public function test_validates_email_format(): void
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'customer_type' => 'individual',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crm/customers', $customerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_user_cannot_access_customers(): void
    {
        $response = $this->getJson('/api/v1/crm/customers');

        $response->assertStatus(401);
    }
}
