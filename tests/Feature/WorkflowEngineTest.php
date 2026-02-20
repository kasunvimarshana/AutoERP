<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Enums\WorkflowInstanceStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ── Authentication ──────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_workflow_definitions(): void
    {
        $this->getJson('/api/v1/workflow/definitions')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_create_workflow_definition(): void
    {
        $this->postJson('/api/v1/workflow/definitions', [])->assertStatus(401);
    }

    // ── Authorization ───────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_list_definitions(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/workflow/definitions')
            ->assertStatus(403);
    }

    public function test_user_without_permission_cannot_create_definition(): void
    {
        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/workflow/definitions', [])
            ->assertStatus(403);
    }

    // ── CRUD ────────────────────────────────────────────────────────────────

    public function test_user_with_view_permission_can_list_definitions(): void
    {
        $this->grantPermissions(['workflow.view']);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/workflow/definitions')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_user_can_create_workflow_definition_with_states_and_transitions(): void
    {
        $this->grantPermissions(['workflow.manage']);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/workflow/definitions', [
                'name' => 'Order Approval',
                'entity_type' => 'order',
                'description' => 'Standard order approval workflow',
                'is_active' => true,
                'states' => [
                    ['name' => 'draft', 'label' => 'Draft', 'is_initial' => true, 'is_final' => false],
                    ['name' => 'pending_approval', 'label' => 'Pending Approval', 'is_initial' => false, 'is_final' => false],
                    ['name' => 'approved', 'label' => 'Approved', 'is_initial' => false, 'is_final' => true],
                    ['name' => 'rejected', 'label' => 'Rejected', 'is_initial' => false, 'is_final' => true],
                ],
                'transitions' => [
                    ['name' => 'submit', 'from_state_name' => 'draft', 'to_state_name' => 'pending_approval'],
                    ['name' => 'approve', 'from_state_name' => 'pending_approval', 'to_state_name' => 'approved', 'required_permission' => 'orders.confirm'],
                    ['name' => 'reject', 'from_state_name' => 'pending_approval', 'to_state_name' => 'rejected'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Order Approval')
            ->assertJsonPath('entity_type', 'order');

        $this->assertDatabaseHas('workflow_definitions', [
            'name' => 'Order Approval',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertDatabaseCount('workflow_states', 4);
        $this->assertDatabaseCount('workflow_transitions', 3);
    }

    public function test_user_can_update_workflow_definition(): void
    {
        $this->grantPermissions(['workflow.manage']);

        $definition = $this->createDefinitionFixture();

        $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/workflow/definitions/{$definition->id}", [
                'description' => 'Updated description',
                'is_active' => false,
            ])
            ->assertStatus(200)
            ->assertJsonPath('description', 'Updated description')
            ->assertJsonPath('is_active', false);
    }

    // ── Instance lifecycle ──────────────────────────────────────────────────

    public function test_user_can_start_workflow_instance(): void
    {
        $this->grantPermissions(['workflow.manage']);

        $definition = $this->createDefinitionFixture();
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/workflow/instances', [
                'workflow_definition_id' => $definition->id,
                'entity_type' => 'order',
                'entity_id' => $entityId,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('entity_type', 'order')
            ->assertJsonPath('entity_id', $entityId)
            ->assertJsonPath('status', WorkflowInstanceStatus::Active->value);

        $this->assertDatabaseHas('workflow_instances', [
            'entity_id' => $entityId,
            'tenant_id' => $this->tenant->id,
        ]);

        // Initial history record should exist
        $this->assertDatabaseHas('workflow_histories', [
            'workflow_instance_id' => $response->json('id'),
        ]);
    }

    public function test_user_can_transition_workflow_instance(): void
    {
        $this->grantPermissions(['workflow.manage']);

        $definition = $this->createDefinitionFixture();
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $instanceId = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/workflow/instances', [
                'workflow_definition_id' => $definition->id,
                'entity_type' => 'order',
                'entity_id' => $entityId,
            ])->json('id');

        // Get the 'submit' transition
        $submitTransition = WorkflowTransition::where('workflow_definition_id', $definition->id)
            ->where('name', 'submit')
            ->first();

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/workflow/instances/{$instanceId}/transition", [
                'transition_id' => $submitTransition->id,
                'comment' => 'Submitting for approval',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', WorkflowInstanceStatus::Active->value);

        // The current state should now be pending_approval
        $instance = WorkflowInstance::find($instanceId);
        $this->assertEquals('pending_approval', $instance->currentState->name);
    }

    public function test_transition_to_final_state_completes_instance(): void
    {
        $this->grantPermissions(['workflow.manage']);

        $definition = $this->createDefinitionFixture();
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $instanceId = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/workflow/instances', [
                'workflow_definition_id' => $definition->id,
                'entity_type' => 'order',
                'entity_id' => $entityId,
            ])->json('id');

        // Submit
        $submitTransition = WorkflowTransition::where('workflow_definition_id', $definition->id)
            ->where('name', 'submit')->first();

        $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/workflow/instances/{$instanceId}/transition", [
                'transition_id' => $submitTransition->id,
            ])->assertStatus(200);

        // Approve (final state)
        $approveTransition = WorkflowTransition::where('workflow_definition_id', $definition->id)
            ->where('name', 'approve')->first();

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/workflow/instances/{$instanceId}/transition", [
                'transition_id' => $approveTransition->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', WorkflowInstanceStatus::Completed->value);

        $this->assertNotNull($response->json('completed_at'));
    }

    public function test_user_can_cancel_workflow_instance(): void
    {
        $this->grantPermissions(['workflow.manage']);

        $definition = $this->createDefinitionFixture();
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $instanceId = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/workflow/instances', [
                'workflow_definition_id' => $definition->id,
                'entity_type' => 'order',
                'entity_id' => $entityId,
            ])->json('id');

        $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/workflow/instances/{$instanceId}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('status', WorkflowInstanceStatus::Cancelled->value);
    }

    public function test_cannot_transition_cancelled_instance(): void
    {
        $this->grantPermissions(['workflow.manage']);

        $definition = $this->createDefinitionFixture();
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $instanceId = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/workflow/instances', [
                'workflow_definition_id' => $definition->id,
                'entity_type' => 'order',
                'entity_id' => $entityId,
            ])->json('id');

        // Cancel it first
        $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/workflow/instances/{$instanceId}/cancel")
            ->assertStatus(200);

        // Now try to transition — should fail (instance no longer active)
        $submitTransition = WorkflowTransition::where('workflow_definition_id', $definition->id)
            ->where('name', 'submit')->first();

        $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/workflow/instances/{$instanceId}/transition", [
                'transition_id' => $submitTransition->id,
            ])
            ->assertStatus(404);
    }

    public function test_user_can_get_entity_workflow_instance(): void
    {
        $this->grantPermissions(['workflow.manage', 'workflow.view']);

        $definition = $this->createDefinitionFixture();
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/workflow/instances', [
                'workflow_definition_id' => $definition->id,
                'entity_type' => 'order',
                'entity_id' => $entityId,
            ])->assertStatus(201);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/workflow/instances/entity?entity_type=order&entity_id='.$entityId)
            ->assertStatus(200)
            ->assertJsonPath('entity_id', $entityId);
    }

    public function test_get_entity_instance_returns_404_when_none_exists(): void
    {
        $this->grantPermissions(['workflow.view']);

        $entityId = (string) \Illuminate\Support\Str::uuid();

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/workflow/instances/entity?entity_type=order&entity_id='.$entityId)
            ->assertStatus(404);
    }

    // ── Tenant isolation ────────────────────────────────────────────────────

    public function test_tenant_cannot_see_other_tenant_definitions(): void
    {
        $this->grantPermissions(['workflow.view']);

        $otherTenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        WorkflowDefinition::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Workflow',
            'entity_type' => 'order',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/workflow/definitions')
            ->assertStatus(200);

        $this->assertEmpty($response->json('data'));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function createDefinitionFixture(): WorkflowDefinition
    {
        $definition = WorkflowDefinition::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Order Approval',
            'entity_type' => 'order',
            'is_active' => true,
        ]);

        $draft = WorkflowState::create([
            'tenant_id' => $this->tenant->id,
            'workflow_definition_id' => $definition->id,
            'name' => 'draft',
            'label' => 'Draft',
            'is_initial' => true,
            'is_final' => false,
        ]);

        $pending = WorkflowState::create([
            'tenant_id' => $this->tenant->id,
            'workflow_definition_id' => $definition->id,
            'name' => 'pending_approval',
            'label' => 'Pending Approval',
            'is_initial' => false,
            'is_final' => false,
        ]);

        $approved = WorkflowState::create([
            'tenant_id' => $this->tenant->id,
            'workflow_definition_id' => $definition->id,
            'name' => 'approved',
            'label' => 'Approved',
            'is_initial' => false,
            'is_final' => true,
        ]);

        WorkflowTransition::create([
            'tenant_id' => $this->tenant->id,
            'workflow_definition_id' => $definition->id,
            'from_state_id' => $draft->id,
            'to_state_id' => $pending->id,
            'name' => 'submit',
        ]);

        WorkflowTransition::create([
            'tenant_id' => $this->tenant->id,
            'workflow_definition_id' => $definition->id,
            'from_state_id' => $pending->id,
            'to_state_id' => $approved->id,
            'name' => 'approve',
        ]);

        return $definition;
    }

    /** @param array<string> $permissions */
    private function grantPermissions(array $permissions): void
    {
        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
            $this->user->givePermissionTo($permission);
        }
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
