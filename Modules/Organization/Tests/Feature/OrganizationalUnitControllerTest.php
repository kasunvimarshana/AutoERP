<?php

declare(strict_types=1);

namespace Modules\Organization\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Entities\Location;
use Modules\Organization\Entities\OrganizationalUnit;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

class OrganizationalUnitControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::factory()->create();

        // Create user with organizational unit management permissions
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permissions' => [
                'organizational_unit.view',
                'organizational_unit.create',
                'organizational_unit.update',
                'organizational_unit.delete',
            ],
        ]);

        // Create organization
        $this->organization = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_it_lists_organizational_units(): void
    {
        OrganizationalUnit::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/organizational-units');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'unit_type',
                        'status',
                        'organization_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_it_creates_organizational_unit(): void
    {
        $data = [
            'organization_id' => $this->organization->id,
            'code' => 'UNIT-001',
            'name' => [
                'en' => 'Engineering Department',
                'es' => 'Departamento de Ingeniería',
            ],
            'description' => [
                'en' => 'Software engineering team',
            ],
            'unit_type' => 'department',
            'status' => 'active',
            'email' => 'engineering@example.com',
            'phone' => '+1234567890',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/organizational-units', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'name',
                    'unit_type',
                    'status',
                ],
            ])
            ->assertJsonPath('data.code', 'UNIT-001')
            ->assertJsonPath('data.unit_type', 'department')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('organizational_units', [
            'code' => 'UNIT-001',
            'unit_type' => 'department',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_it_shows_organizational_unit(): void
    {
        $unit = OrganizationalUnit::factory()->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/organizational-units/{$unit->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'name',
                    'unit_type',
                    'status',
                ],
            ])
            ->assertJsonPath('data.id', $unit->id);
    }

    public function test_it_updates_organizational_unit(): void
    {
        $unit = OrganizationalUnit::factory()->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        $data = [
            'name' => [
                'en' => 'Updated Department Name',
            ],
            'status' => 'inactive',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/organizational-units/{$unit->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('organizational_units', [
            'id' => $unit->id,
            'status' => 'inactive',
        ]);
    }

    public function test_it_deletes_organizational_unit(): void
    {
        $unit = OrganizationalUnit::factory()->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/organizational-units/{$unit->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('organizational_units', [
            'id' => $unit->id,
        ]);
    }

    public function test_it_gets_children_of_organizational_unit(): void
    {
        $parent = OrganizationalUnit::factory()->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $this->organization->id,
        ]);

        OrganizationalUnit::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $this->organization->id,
            'parent_unit_id' => $parent->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/organizational-units/{$parent->id}/children");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_it_filters_by_organization(): void
    {
        $otherOrg = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        OrganizationalUnit::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $this->organization->id,
        ]);

        OrganizationalUnit::factory()->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $otherOrg->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/organizational-units?organization_id={$this->organization->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/organizational-units', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id', 'code', 'name', 'unit_type', 'status']);
    }

    public function test_it_validates_unique_code(): void
    {
        $existing = OrganizationalUnit::factory()->create([
            'tenant_id' => $this->tenant->id,
            'organization_id' => $this->organization->id,
            'code' => 'UNIT-DUPLICATE',
        ]);

        $data = [
            'organization_id' => $this->organization->id,
            'code' => 'UNIT-DUPLICATE',
            'name' => ['en' => 'Test Unit'],
            'unit_type' => 'department',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/organizational-units', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_it_returns_404_for_nonexistent_unit(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/organizational-units/99999');

        $response->assertStatus(404);
    }
}
