<?php

declare(strict_types=1);

namespace Modules\Customer\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Models\Customer;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Customer API Feature Test
 *
 * Tests Customer API endpoints
 */
class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user role
        Role::create(['name' => 'user']);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    /**
     * Test can list customers
     */
    public function test_can_list_customers(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'customer_number',
                        'first_name',
                        'last_name',
                        'email',
                        'status',
                        'customer_type',
                    ],
                ],
            ]);
    }

    /**
     * Test can create customer
     */
    public function test_can_create_customer(): void
    {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'customer_type' => 'individual',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/customers', $customerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'customer_number',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
        ]);
    }

    /**
     * Test validation fails with invalid data
     */
    public function test_validation_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/customers', [
                'first_name' => '',
                'email' => 'invalid-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    /**
     * Test can get single customer
     */
    public function test_can_get_single_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'customer_number',
                    'first_name',
                    'last_name',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $customer->id,
                    'email' => $customer->email,
                ],
            ]);
    }

    /**
     * Test can update customer
     */
    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/customers/{$customer->id}", [
                'first_name' => 'Jane',
                'last_name' => $customer->last_name,
                'customer_type' => $customer->customer_type,
                'status' => $customer->status,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'first_name' => 'Jane',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Jane',
        ]);
    }

    /**
     * Test can delete customer
     */
    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
    }

    /**
     * Test can search customers
     */
    public function test_can_search_customers(): void
    {
        Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/customers/search?query=John');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /**
     * Test can get customer with vehicles
     */
    public function test_can_get_customer_with_vehicles(): void
    {
        $customer = Customer::factory()
            ->hasVehicles(2)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/customers/{$customer->id}/vehicles");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'vehicles' => [
                        '*' => [
                            'id',
                            'vehicle_number',
                            'make',
                            'model',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test can get customer statistics
     */
    public function test_can_get_customer_statistics(): void
    {
        $customer = Customer::factory()
            ->hasVehicles(2)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/customers/{$customer->id}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_vehicles',
                    'total_service_records',
                ],
            ]);
    }

    /**
     * Test authentication required
     */
    public function test_authentication_required(): void
    {
        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(401);
    }
}
