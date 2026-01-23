<?php

namespace Tests\Feature\Modules\CustomerManagement;

use Tests\TestCase;
use App\Modules\CustomerManagement\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_customers(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'uuid',
                            'customer_code',
                            'first_name',
                            'last_name',
                            'email',
                        ]
                    ]
                ]
            ]);
    }

    public function test_can_create_customer(): void
    {
        $data = [
            'customer_type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'address_line1' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
        ];

        $response = $this->postJson('/api/v1/customers', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer created successfully',
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
        ]);
    }

    public function test_can_show_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'email' => $customer->email,
                ]
            ]);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->putJson("/api/v1/customers/{$customer->id}", [
            'first_name' => 'Updated',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated successfully',
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Updated',
        ]);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer deleted successfully',
            ]);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_validation_fails_for_invalid_email(): void
    {
        $data = [
            'customer_type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'phone' => '+1234567890',
        ];

        $response = $this->postJson('/api/v1/customers', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
