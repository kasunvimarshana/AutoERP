<?php

declare(strict_types=1);

namespace Modules\Organization\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Branch API Feature Test
 *
 * Tests Branch API endpoints
 */
class BranchApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role with permissions
        $role = Role::create(['name' => 'admin']);

        Permission::create(['name' => 'branch.list']);
        Permission::create(['name' => 'branch.read']);
        Permission::create(['name' => 'branch.create']);
        Permission::create(['name' => 'branch.update']);
        Permission::create(['name' => 'branch.delete']);

        $role->givePermissionTo([
            'branch.list',
            'branch.read',
            'branch.create',
            'branch.update',
            'branch.delete',
        ]);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        // Create organization for testing
        $this->organization = Organization::factory()->create();
    }

    /**
     * Test can list branches
     */
    public function test_can_list_branches(): void
    {
        Branch::factory()->count(3)->forOrganization($this->organization)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/branches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'branch_code',
                        'name',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test can create branch
     */
    public function test_can_create_branch(): void
    {
        $branchData = [
            'organization_id' => $this->organization->id,
            'name' => 'Downtown Branch',
            'email' => 'downtown@autoservice.com',
            'phone' => '+1234567890',
            'city' => 'New York',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/branches', $branchData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'branch_code',
                    'name',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('branches', [
            'name' => 'Downtown Branch',
            'email' => 'downtown@autoservice.com',
        ]);
    }

    /**
     * Test can get branch details
     */
    public function test_can_get_branch_details(): void
    {
        $branch = Branch::factory()->forOrganization($this->organization)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                ],
            ]);
    }

    /**
     * Test can update branch
     */
    public function test_can_update_branch(): void
    {
        $branch = Branch::factory()->forOrganization($this->organization)->create();

        $updateData = [
            'name' => 'Updated Branch Name',
            'phone' => '+9876543210',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/branches/{$branch->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $branch->id,
                    'name' => 'Updated Branch Name',
                ],
            ]);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Updated Branch Name',
        ]);
    }

    /**
     * Test can delete branch
     */
    public function test_can_delete_branch(): void
    {
        $branch = Branch::factory()->forOrganization($this->organization)->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('branches', [
            'id' => $branch->id,
        ]);
    }

    /**
     * Test can get branches by organization
     */
    public function test_can_get_branches_by_organization(): void
    {
        Branch::factory()->count(3)->forOrganization($this->organization)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/branches/organization/{$this->organization->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test can search branches
     */
    public function test_can_search_branches(): void
    {
        Branch::factory()->forOrganization($this->organization)->create(['name' => 'Downtown Branch']);
        Branch::factory()->forOrganization($this->organization)->create(['name' => 'Uptown Branch']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/branches/search?query=Downtown');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Downtown Branch']);
    }

    /**
     * Test can check branch capacity
     */
    public function test_can_check_branch_capacity(): void
    {
        $branch = Branch::factory()
            ->forOrganization($this->organization)
            ->create(['capacity_vehicles' => 20]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/branches/{$branch->id}/capacity?current_vehicles=5");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_capacity' => true,
                    'available_capacity' => 15,
                    'total_capacity' => 20,
                    'current_usage' => 5,
                ],
            ]);
    }

    /**
     * Test validation fails for missing required fields
     */
    public function test_validation_fails_for_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/branches', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id', 'name']);
    }
}
