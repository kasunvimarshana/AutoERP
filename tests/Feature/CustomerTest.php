<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\CRM\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    public function test_can_list_customers(): void
    {
        Customer::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'email', 'code']
                ],
                'meta' => ['current_page', 'per_page', 'total']
            ]);
    }

    public function test_can_create_individual_customer(): void
    {
        $customerData = [
            'type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/customers', $customerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'code', 'email']
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_business_customer(): void
    {
        $customerData = [
            'type' => 'business',
            'company_name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            'phone' => '+1234567890',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/customers', $customerData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('customers', [
            'company_name' => 'Acme Corp',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_cannot_create_customer_with_duplicate_email(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'duplicate@example.com'
        ]);

        $customerData = [
            'type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'duplicate@example.com',
            'phone' => '+1234567890',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/customers', $customerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $updateData = ['phone' => '+9876543210'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/customers/{$customer->id}", $updateData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '+9876543210',
        ]);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200);
        
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_can_search_customers(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'John',
            'email' => 'john@example.com'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/customers/search?q=John');

        $response->assertStatus(200)
            ->assertJsonFragment(['first_name' => 'John']);
    }

    public function test_tenant_isolation_works(): void
    {
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => 'active',
        ]);

        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/customers/{$otherCustomer->id}");

        $response->assertStatus(404);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(401);
    }
}
