<?php

declare(strict_types=1);

namespace Modules\Appointment\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Appointment\Models\Bay;
use Modules\Organization\Models\Branch;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bay API Feature Test
 *
 * Tests Bay API endpoints
 */
class BayApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Branch $branch;

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
    }

    /**
     * Test can list bays
     */
    public function test_can_list_bays(): void
    {
        Bay::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/bays');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'branch_id',
                        'bay_number',
                        'bay_type',
                        'status',
                        'capacity',
                    ],
                ],
            ]);
    }

    /**
     * Test can create bay
     */
    public function test_can_create_bay(): void
    {
        $data = [
            'branch_id' => $this->branch->id,
            'bay_number' => 'BAY-001',
            'bay_type' => 'standard',
            'status' => 'available',
            'capacity' => 1,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bays', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'bay_number',
                    'bay_type',
                ],
            ]);

        $this->assertDatabaseHas('bays', [
            'branch_id' => $this->branch->id,
            'bay_number' => 'BAY-001',
        ]);
    }

    /**
     * Test can show bay
     */
    public function test_can_show_bay(): void
    {
        $bay = Bay::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/bays/{$bay->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'bay_number',
                    'bay_type',
                    'status',
                ],
            ]);
    }

    /**
     * Test can update bay
     */
    public function test_can_update_bay(): void
    {
        $bay = Bay::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $data = [
            'status' => 'maintenance',
            'notes' => 'Under maintenance',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/bays/{$bay->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bays', [
            'id' => $bay->id,
            'status' => 'maintenance',
        ]);
    }

    /**
     * Test can delete bay
     */
    public function test_can_delete_bay(): void
    {
        $bay = Bay::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/bays/{$bay->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('bays', [
            'id' => $bay->id,
        ]);
    }
}
