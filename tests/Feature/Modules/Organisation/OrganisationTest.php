<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Organisation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganisationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'Org Test Tenant',
            'slug' => 'org-test-tenant',
        ]);
        $this->tenantId = $response->json('data.id');
    }

    public function test_can_create_organisation(): void
    {
        $response = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Head Office',
            'code' => 'HO',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'tenant_id', 'parent_id', 'type', 'name', 'code', 'description', 'status', 'meta', 'created_at'],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Head Office')
            ->assertJsonPath('data.code', 'HO')
            ->assertJsonPath('data.type', 'organisation')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.parent_id', null)
            ->assertJsonPath('data.tenant_id', $this->tenantId);
    }

    public function test_code_is_normalised_to_uppercase(): void
    {
        $response = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Head Office',
            'code' => 'ho-main',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'HO-MAIN');
    }

    public function test_code_must_be_unique_per_tenant(): void
    {
        $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Head Office',
            'code' => 'HO',
        ]);

        $response = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'branch',
            'name' => 'Another Node',
            'code' => 'HO',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_type_validation(): void
    {
        $response = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'invalid_type',
            'name' => 'Bad Node',
            'code' => 'BAD',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_create_branch_under_organisation(): void
    {
        $orgResponse = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Head Office',
            'code' => 'HO',
        ]);
        $orgId = $orgResponse->json('data.id');

        $response = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'branch',
            'name' => 'North Branch',
            'code' => 'BR-NORTH',
            'parent_id' => $orgId,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.parent_id', $orgId)
            ->assertJsonPath('data.type', 'branch');
    }

    public function test_parent_must_belong_to_same_tenant(): void
    {
        $response = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'branch',
            'name' => 'Orphan Branch',
            'code' => 'ORPHAN',
            'parent_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_list_organisations(): void
    {
        $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId, 'type' => 'organisation', 'name' => 'Org A', 'code' => 'ORG-A',
        ]);
        $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId, 'type' => 'organisation', 'name' => 'Org B', 'code' => 'ORG-B',
        ]);

        $response = $this->getJson("/api/v1/organisations?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_get_organisation_by_id(): void
    {
        $createResponse = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Head Office',
            'code' => 'HO',
        ]);
        $id = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/organisations/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Head Office');
    }

    public function test_returns_404_for_nonexistent_organisation(): void
    {
        $response = $this->getJson("/api/v1/organisations/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_organisation(): void
    {
        $createResponse = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Head Office',
            'code' => 'HO',
        ]);
        $id = $createResponse->json('data.id');

        $response = $this->putJson("/api/v1/organisations/{$id}", [
            'tenant_id' => $this->tenantId,
            'name' => 'Global Head Office',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Global Head Office')
            ->assertJsonPath('data.code', 'HO');
    }

    public function test_can_update_organisation_status(): void
    {
        $createResponse = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Head Office',
            'code' => 'HO',
        ]);
        $id = $createResponse->json('data.id');

        $response = $this->putJson("/api/v1/organisations/{$id}", [
            'tenant_id' => $this->tenantId,
            'name' => 'Head Office',
            'status' => 'inactive',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_can_delete_organisation(): void
    {
        $createResponse = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Temp Office',
            'code' => 'TEMP',
        ]);
        $id = $createResponse->json('data.id');

        $this->deleteJson("/api/v1/organisations/{$id}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/organisations/{$id}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_can_retrieve_children(): void
    {
        $orgResponse = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'organisation',
            'name' => 'Head Office',
            'code' => 'HO',
        ]);
        $orgId = $orgResponse->json('data.id');

        $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'branch',
            'name' => 'North Branch',
            'code' => 'BR-N',
            'parent_id' => $orgId,
        ]);
        $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId,
            'type' => 'branch',
            'name' => 'South Branch',
            'code' => 'BR-S',
            'parent_id' => $orgId,
        ]);

        $response = $this->getJson("/api/v1/organisations/{$orgId}/children?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_deep_hierarchy_organisation_branch_location_department(): void
    {
        // Organisation
        $orgId = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId, 'type' => 'organisation', 'name' => 'Corp', 'code' => 'CORP',
        ])->json('data.id');

        // Branch
        $branchId = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId, 'type' => 'branch', 'name' => 'North', 'code' => 'NORTH', 'parent_id' => $orgId,
        ])->json('data.id');

        // Location
        $locationId = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId, 'type' => 'location', 'name' => 'Floor 1', 'code' => 'FL1', 'parent_id' => $branchId,
        ])->json('data.id');

        // Department
        $deptResponse = $this->postJson('/api/v1/organisations', [
            'tenant_id' => $this->tenantId, 'type' => 'department', 'name' => 'HR', 'code' => 'HR-FL1', 'parent_id' => $locationId,
        ]);

        $deptResponse->assertStatus(201)
            ->assertJsonPath('data.type', 'department')
            ->assertJsonPath('data.parent_id', $locationId);
    }
}
