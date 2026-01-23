<?php

declare(strict_types=1);

namespace Modules\Organization\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Organization API Feature Test
 *
 * Tests Organization API endpoints
 */
class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role with permissions
        $role = Role::create(['name' => 'admin']);

        Permission::create(['name' => 'organization.list']);
        Permission::create(['name' => 'organization.read']);
        Permission::create(['name' => 'organization.create']);
        Permission::create(['name' => 'organization.update']);
        Permission::create(['name' => 'organization.delete']);

        $role->givePermissionTo([
            'organization.list',
            'organization.read',
            'organization.create',
            'organization.update',
            'organization.delete',
        ]);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    /**
     * Test can list organizations
     */
    public function test_can_list_organizations(): void
    {
        Organization::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/organizations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'organization_number',
                        'name',
                        'type',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test can create organization
     */
    public function test_can_create_organization(): void
    {
        $organizationData = [
            'name' => 'Test Auto Service',
            'legal_name' => 'Test Auto Service Ltd.',
            'type' => 'single',
            'email' => 'test@autoservice.com',
            'phone' => '+1234567890',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/organizations', $organizationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'organization_number',
                    'name',
                    'type',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Auto Service',
            'email' => 'test@autoservice.com',
        ]);
    }

    /**
     * Test can get organization details
     */
    public function test_can_get_organization_details(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/organizations/{$organization->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                ],
            ]);
    }

    /**
     * Test can update organization
     */
    public function test_can_update_organization(): void
    {
        $organization = Organization::factory()->create();

        $updateData = [
            'name' => 'Updated Auto Service',
            'phone' => '+9876543210',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/organizations/{$organization->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $organization->id,
                    'name' => 'Updated Auto Service',
                ],
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Updated Auto Service',
        ]);
    }

    /**
     * Test can delete organization
     */
    public function test_can_delete_organization(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/organizations/{$organization->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('organizations', [
            'id' => $organization->id,
        ]);
    }

    /**
     * Test can search organizations
     */
    public function test_can_search_organizations(): void
    {
        Organization::factory()->create(['name' => 'ABC Auto Service']);
        Organization::factory()->create(['name' => 'XYZ Motors']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/organizations/search?query=ABC');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'ABC Auto Service']);
    }

    /**
     * Test validation fails for missing required fields
     */
    public function test_validation_fails_for_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/organizations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    /**
     * Test duplicate organization number is not allowed
     */
    public function test_duplicate_organization_number_not_allowed(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/organizations', [
                'organization_number' => $organization->organization_number,
                'name' => 'New Organization',
                'type' => 'single',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organization_number']);
    }
}
