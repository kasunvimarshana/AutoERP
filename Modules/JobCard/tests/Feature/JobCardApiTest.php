<?php

declare(strict_types=1);

namespace Modules\JobCard\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\JobCard\Models\JobCard;
use Modules\Organization\Models\Branch;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * JobCard API Feature Test
 *
 * Tests JobCard API endpoints
 */
class JobCardApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Vehicle $vehicle;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'user']);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);
        $this->branch = Branch::factory()->create();
    }

    /**
     * Test can list job cards
     */
    public function test_can_list_job_cards(): void
    {
        JobCard::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/job-cards');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'job_number',
                        'status',
                        'priority',
                    ],
                ],
            ]);
    }

    /**
     * Test can create job card
     */
    public function test_can_create_job_card(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'priority' => 'normal',
            'estimated_hours' => 5.5,
            'customer_complaints' => 'Engine noise',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/job-cards', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'job_number',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('job_cards', [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
        ]);
    }

    /**
     * Test can show job card
     */
    public function test_can_show_job_card(): void
    {
        $jobCard = JobCard::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/job-cards/{$jobCard->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'job_number',
                    'status',
                ],
            ]);
    }

    /**
     * Test can update job card
     */
    public function test_can_update_job_card(): void
    {
        $jobCard = JobCard::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $data = [
            'priority' => 'high',
            'notes' => 'Updated notes',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/job-cards/{$jobCard->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('job_cards', [
            'id' => $jobCard->id,
            'priority' => 'high',
        ]);
    }

    /**
     * Test can delete job card
     */
    public function test_can_delete_job_card(): void
    {
        $jobCard = JobCard::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/job-cards/{$jobCard->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('job_cards', [
            'id' => $jobCard->id,
        ]);
    }

    /**
     * Test can start job card
     */
    public function test_can_start_job_card(): void
    {
        $jobCard = JobCard::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/job-cards/{$jobCard->id}/start");

        $response->assertStatus(200);

        $this->assertDatabaseHas('job_cards', [
            'id' => $jobCard->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Test can add task to job card
     */
    public function test_can_add_task_to_job_card(): void
    {
        $jobCard = JobCard::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $data = [
            'task_description' => 'Change oil filter',
            'estimated_time' => 0.5,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/job-cards/{$jobCard->id}/tasks", $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('job_tasks', [
            'job_card_id' => $jobCard->id,
            'task_description' => 'Change oil filter',
        ]);
    }

    /**
     * Test can add part to job card
     */
    public function test_can_add_part_to_job_card(): void
    {
        $jobCard = JobCard::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $data = [
            'quantity' => 2,
            'unit_price' => 25.00,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/job-cards/{$jobCard->id}/parts", $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('job_parts', [
            'job_card_id' => $jobCard->id,
            'quantity' => 2,
        ]);
    }

    /**
     * Test validation fails with invalid data
     */
    public function test_validation_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/job-cards', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'vehicle_id', 'branch_id']);
    }
}
