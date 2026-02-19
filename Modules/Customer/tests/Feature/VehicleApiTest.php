<?php

declare(strict_types=1);

namespace Modules\Customer\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Vehicle API Feature Test
 *
 * Tests Vehicle API endpoints
 */
class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user role
        Role::create(['name' => 'user']);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        // Create a customer for testing
        $this->customer = Customer::factory()->create();
    }

    /**
     * Test can list vehicles
     */
    public function test_can_list_vehicles(): void
    {
        Vehicle::factory()->count(3)->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/vehicles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'vehicle_number',
                        'make',
                        'model',
                        'year',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test can create vehicle
     */
    public function test_can_create_vehicle(): void
    {
        $vehicleData = [
            'customer_id' => $this->customer->id,
            'registration_number' => 'ABC123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'current_mileage' => 5000,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/vehicles', $vehicleData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'vehicle_number',
                    'make',
                    'model',
                    'registration_number',
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'registration_number' => 'ABC123',
            'make' => 'Toyota',
        ]);
    }

    /**
     * Test validation fails with invalid vehicle data
     */
    public function test_validation_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/vehicles', [
                'customer_id' => 999999, // Non-existent customer
                'make' => '',
                'year' => 1800, // Invalid year
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'registration_number', 'make', 'model', 'year']);
    }

    /**
     * Test can get single vehicle
     */
    public function test_can_get_single_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'vehicle_number',
                    'make',
                    'model',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $vehicle->id,
                    'make' => $vehicle->make,
                ],
            ]);
    }

    /**
     * Test can update vehicle
     */
    public function test_can_update_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'make' => 'Toyota',
            'registration_number' => 'UNIQUE123',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/vehicles/{$vehicle->id}", [
                'customer_id' => $this->customer->id,
                'registration_number' => 'UNIQUE123', // Keep same registration
                'make' => 'Honda',
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'current_mileage' => $vehicle->current_mileage,
                'status' => $vehicle->status,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'make' => 'Honda',
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'make' => 'Honda',
        ]);
    }

    /**
     * Test can delete vehicle
     */
    public function test_can_delete_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('vehicles', [
            'id' => $vehicle->id,
        ]);
    }

    /**
     * Test can get vehicles by customer
     */
    public function test_can_get_vehicles_by_customer(): void
    {
        Vehicle::factory()->count(3)->create(['customer_id' => $this->customer->id]);

        // Create vehicles for another customer
        $otherCustomer = Customer::factory()->create();
        Vehicle::factory()->count(2)->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/customers/{$this->customer->id}/vehicles");

        $response->assertStatus(200);

        // Should only return 3 vehicles
        $this->assertCount(3, $response->json('data.vehicles'));
    }

    /**
     * Test can update vehicle mileage
     */
    public function test_can_update_vehicle_mileage(): void
    {
        $vehicle = Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'current_mileage' => 5000,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/vehicles/{$vehicle->id}/mileage", [
                'mileage' => 6000,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'current_mileage' => 6000,
        ]);
    }

    /**
     * Test can transfer vehicle ownership
     */
    public function test_can_transfer_vehicle_ownership(): void
    {
        $vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);
        $newCustomer = Customer::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/vehicles/{$vehicle->id}/transfer-ownership", [
                'new_customer_id' => $newCustomer->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'customer_id' => $newCustomer->id,
        ]);
    }

    /**
     * Test can get vehicles due for service
     */
    public function test_can_get_vehicles_due_for_service(): void
    {
        // Create vehicle due for service
        Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'next_service_date' => now()->subDays(5), // Overdue
        ]);

        // Create vehicle not due
        Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'next_service_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/vehicles/due-for-service');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /**
     * Test can get vehicles with expiring insurance
     */
    public function test_can_get_vehicles_with_expiring_insurance(): void
    {
        // Create vehicle with expiring insurance
        Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'insurance_expiry' => now()->addDays(15), // Expiring soon
        ]);

        // Create vehicle with valid insurance
        Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'insurance_expiry' => now()->addMonths(6),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/vehicles/expiring-insurance?days=30');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /**
     * Test authentication required
     */
    public function test_authentication_required(): void
    {
        $response = $this->getJson('/api/v1/vehicles');

        $response->assertStatus(401);
    }
}
