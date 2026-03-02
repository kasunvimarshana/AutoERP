<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    /** @var array<string, mixed> */
    private array $defaultStates = [
        ['name' => 'Draft', 'is_initial' => true, 'is_final' => false, 'sort_order' => 1],
        ['name' => 'In Review', 'is_initial' => false, 'is_final' => false, 'sort_order' => 2],
        ['name' => 'Approved', 'is_initial' => false, 'is_final' => true, 'sort_order' => 3],
    ];

    /** @var array<string, mixed> */
    private array $defaultTransitions = [
        ['name' => 'Submit', 'from_state_name' => 'Draft', 'to_state_name' => 'In Review'],
        ['name' => 'Approve', 'from_state_name' => 'In Review', 'to_state_name' => 'Approved'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'Workflow Test Tenant',
            'slug' => 'workflow-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');
    }

    // ─────────────────────────────────────────────
    // Workflow Definition tests
    // ─────────────────────────────────────────────

    public function test_can_create_workflow_definition(): void
    {
        $response = $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Document Approval',
            'description' => 'Standard document approval workflow',
            'entity_type' => 'document',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Document Approval')
            ->assertJsonPath('data.entity_type', 'document')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'name', 'description', 'entity_type',
                'status', 'is_active', 'created_at', 'updated_at',
            ]]);
    }

    public function test_unique_name_per_tenant_validation(): void
    {
        $payload = [
            'tenant_id' => $this->tenantId,
            'name' => 'Duplicate Name',
            'entity_type' => 'order',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ];

        $this->postJson('/api/v1/workflows', $payload)->assertStatus(201);

        $response = $this->postJson('/api/v1/workflows', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_list_workflow_definitions(): void
    {
        $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Workflow One',
            'entity_type' => 'invoice',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->assertStatus(201);

        $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Workflow Two',
            'entity_type' => 'order',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/workflows?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_can_get_workflow_definition_by_id(): void
    {
        $created = $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Find Me Workflow',
            'entity_type' => 'task',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->json('data');

        $response = $this->getJson("/api/v1/workflows/{$created['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $created['id'])
            ->assertJsonPath('data.name', 'Find Me Workflow');
    }

    public function test_returns_404_for_nonexistent_definition(): void
    {
        $response = $this->getJson("/api/v1/workflows/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_workflow_definition(): void
    {
        $created = $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Update Me',
            'entity_type' => 'document',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->json('data');

        $response = $this->putJson(
            "/api/v1/workflows/{$created['id']}?tenant_id={$this->tenantId}",
            ['name' => 'Updated Name', 'is_active' => false]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_can_delete_workflow_definition(): void
    {
        $created = $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Delete Me Definition',
            'entity_type' => 'document',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->json('data');

        $this->deleteJson("/api/v1/workflows/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/workflows/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_can_get_states_for_definition(): void
    {
        $created = $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'States Test Workflow',
            'entity_type' => 'document',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->json('data');

        $response = $this->getJson("/api/v1/workflows/{$created['id']}/states?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'workflow_definition_id', 'name', 'is_initial', 'is_final', 'sort_order']]]);
    }

    public function test_can_get_transitions_for_definition(): void
    {
        $created = $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Transitions Test Workflow',
            'entity_type' => 'document',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->json('data');

        $response = $this->getJson("/api/v1/workflows/{$created['id']}/transitions?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'workflow_definition_id', 'from_state_id', 'to_state_id', 'name']]]);
    }

    // ─────────────────────────────────────────────
    // Workflow Instance tests
    // ─────────────────────────────────────────────

    private function createDefinitionWithTransitions(): array
    {
        $def = $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Instance Test Workflow',
            'entity_type' => 'document',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->json('data');

        $states = $this->getJson("/api/v1/workflows/{$def['id']}/states?tenant_id={$this->tenantId}")
            ->json('data');

        $transitions = $this->getJson("/api/v1/workflows/{$def['id']}/transitions?tenant_id={$this->tenantId}")
            ->json('data');

        return ['definition' => $def, 'states' => $states, 'transitions' => $transitions];
    }

    public function test_can_start_workflow_instance(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $response = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 42,
            'started_by_user_id' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.entity_type', 'document')
            ->assertJsonPath('data.entity_id', 42)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'workflow_definition_id', 'entity_type',
                'entity_id', 'current_state_id', 'status', 'started_at',
                'completed_at', 'started_by_user_id', 'created_at', 'updated_at',
            ]]);

        // Should be placed at the initial state
        $initialState = collect($data['states'])->firstWhere('is_initial', true);
        $response->assertJsonPath('data.current_state_id', $initialState['id']);
    }

    public function test_start_on_inactive_definition_is_rejected(): void
    {
        $created = $this->postJson('/api/v1/workflows', [
            'tenant_id' => $this->tenantId,
            'name' => 'Inactive Workflow',
            'entity_type' => 'document',
            'states' => $this->defaultStates,
            'transitions' => $this->defaultTransitions,
        ])->json('data');

        // Deactivate the definition
        $this->putJson("/api/v1/workflows/{$created['id']}?tenant_id={$this->tenantId}", [
            'is_active' => false,
        ])->assertStatus(200);

        $response = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $created['id'],
            'entity_type' => 'document',
            'entity_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_start_on_nonexistent_definition_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => 99999,
            'entity_type' => 'document',
            'entity_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_get_workflow_instance_by_id(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $created = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 5,
        ])->json('data');

        $response = $this->getJson("/api/v1/workflow-instances/{$created['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $created['id'])
            ->assertJsonPath('data.entity_id', 5);
    }

    public function test_returns_404_for_nonexistent_instance(): void
    {
        $response = $this->getJson("/api/v1/workflow-instances/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_list_workflow_instances(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 1,
        ])->assertStatus(201);

        $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 2,
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/workflow-instances?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_can_advance_instance_via_valid_transition(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $instance = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 10,
        ])->json('data');

        // Get the "Submit" transition (Draft → In Review)
        $submitTransition = collect($data['transitions'])->firstWhere('name', 'Submit');
        $inReviewState = collect($data['states'])->firstWhere('name', 'In Review');

        $response = $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            [
                'transition_id' => $submitTransition['id'],
                'actor_user_id' => 1,
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_state_id', $inReviewState['id'])
            ->assertJsonPath('data.status', 'active');
    }

    public function test_advance_with_invalid_transition_is_rejected(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $instance = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 11,
        ])->json('data');

        // Try to use "Approve" transition (In Review → Approved) while in "Draft" state
        $approveTransition = collect($data['transitions'])->firstWhere('name', 'Approve');

        $response = $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            [
                'transition_id' => $approveTransition['id'],
                'actor_user_id' => 1,
            ]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_advance_to_final_state_marks_instance_completed(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $instance = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 20,
        ])->json('data');

        $submitTransition = collect($data['transitions'])->firstWhere('name', 'Submit');
        $approveTransition = collect($data['transitions'])->firstWhere('name', 'Approve');

        // Advance to In Review
        $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            ['transition_id' => $submitTransition['id'], 'actor_user_id' => 1]
        )->assertStatus(200);

        // Advance to Approved (final state)
        $response = $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            ['transition_id' => $approveTransition['id'], 'actor_user_id' => 1]
        );

        $approvedState = collect($data['states'])->firstWhere('name', 'Approved');

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.current_state_id', $approvedState['id']);

        $this->assertNotNull($response->json('data.completed_at'));
    }

    public function test_can_cancel_active_instance(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $instance = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 30,
        ])->json('data');

        $response = $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/cancel?tenant_id={$this->tenantId}",
            ['actor_user_id' => 1, 'comment' => 'Cancelled for testing']
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_advance_cancelled_instance(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $instance = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 31,
        ])->json('data');

        // Cancel the instance
        $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/cancel?tenant_id={$this->tenantId}",
            ['actor_user_id' => 1]
        )->assertStatus(200);

        // Try to advance a cancelled instance
        $submitTransition = collect($data['transitions'])->firstWhere('name', 'Submit');

        $response = $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            ['transition_id' => $submitTransition['id'], 'actor_user_id' => 1]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_advance_completed_instance(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $instance = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 32,
        ])->json('data');

        $submitTransition = collect($data['transitions'])->firstWhere('name', 'Submit');
        $approveTransition = collect($data['transitions'])->firstWhere('name', 'Approve');

        // Drive to completed
        $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            ['transition_id' => $submitTransition['id'], 'actor_user_id' => 1]
        )->assertStatus(200);

        $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            ['transition_id' => $approveTransition['id'], 'actor_user_id' => 1]
        )->assertStatus(200);

        // Try to advance completed instance
        $response = $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            ['transition_id' => $submitTransition['id'], 'actor_user_id' => 1]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_get_instance_logs(): void
    {
        $data = $this->createDefinitionWithTransitions();

        $instance = $this->postJson('/api/v1/workflow-instances', [
            'tenant_id' => $this->tenantId,
            'workflow_definition_id' => $data['definition']['id'],
            'entity_type' => 'document',
            'entity_id' => 40,
            'started_by_user_id' => 1,
        ])->json('data');

        $submitTransition = collect($data['transitions'])->firstWhere('name', 'Submit');

        $this->postJson(
            "/api/v1/workflow-instances/{$instance['id']}/advance?tenant_id={$this->tenantId}",
            ['transition_id' => $submitTransition['id'], 'actor_user_id' => 1]
        )->assertStatus(200);

        $response = $this->getJson("/api/v1/workflow-instances/{$instance['id']}/logs?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data') // start log + advance log
            ->assertJsonStructure(['data' => [[
                'id', 'workflow_instance_id', 'from_state_id', 'to_state_id',
                'transition_id', 'comment', 'actor_user_id', 'acted_at', 'created_at',
            ]]]);
    }
}
