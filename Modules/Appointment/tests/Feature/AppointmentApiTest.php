<?php

declare(strict_types=1);

namespace Modules\Appointment\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Appointment\Models\Appointment;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Organization\Models\Branch;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Appointment API Feature Test
 *
 * Tests Appointment API endpoints
 */
class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Branch $branch;

    private Customer $customer;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user role
        Role::firstOrCreate(['name' => 'user']);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        // Create test data
        $this->branch = Branch::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
        ]);
    }

    /**
     * Test can list appointments
     */
    public function test_can_list_appointments(): void
    {
        Appointment::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/appointments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'appointment_number',
                        'customer_id',
                        'vehicle_id',
                        'branch_id',
                        'service_type',
                        'scheduled_date_time',
                        'duration',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test can create appointment
     */
    public function test_can_create_appointment(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'service_type' => 'oil_change',
            'scheduled_date_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duration' => 60,
            'notes' => 'Test appointment',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/appointments', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'appointment_number',
                    'customer_id',
                    'vehicle_id',
                    'service_type',
                ],
            ]);

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'service_type' => 'oil_change',
        ]);
    }

    /**
     * Test can show appointment
     */
    public function test_can_show_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'appointment_number',
                    'customer_id',
                    'vehicle_id',
                    'status',
                ],
            ]);
    }

    /**
     * Test can update appointment
     */
    public function test_can_update_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $data = [
            'notes' => 'Updated notes',
            'duration' => 90,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/appointments/{$appointment->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'notes' => 'Updated notes',
            'duration' => 90,
        ]);
    }

    /**
     * Test can delete appointment
     */
    public function test_can_delete_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('appointments', [
            'id' => $appointment->id,
        ]);
    }

    /**
     * Test can confirm appointment
     */
    public function test_can_confirm_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/appointments/{$appointment->id}/confirm");

        $response->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    /**
     * Test requires authentication
     */
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/appointments');

        $response->assertStatus(401);
    }
}
