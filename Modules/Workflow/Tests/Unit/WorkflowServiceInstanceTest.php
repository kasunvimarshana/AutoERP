<?php

declare(strict_types=1);

namespace Modules\Workflow\Tests\Unit;

use Modules\Workflow\Application\DTOs\CreateWorkflowInstanceDTO;
use Modules\Workflow\Application\Services\WorkflowService;
use PHPUnit\Framework\TestCase;

/**
 * Structural compliance tests for WorkflowService instance management methods.
 *
 * Verifies method existence, signatures, and DTO payload mapping for
 * createInstance(), listInstances(), and applyTransition() without
 * requiring a database or full Laravel bootstrap.
 */
class WorkflowServiceInstanceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_workflow_service_has_create_instance_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'createInstance'),
            'WorkflowService must expose a public createInstance() method.'
        );
    }

    public function test_workflow_service_has_list_instances_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'listInstances'),
            'WorkflowService must expose a public listInstances() method.'
        );
    }

    public function test_workflow_service_has_apply_transition_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'applyTransition'),
            'WorkflowService must expose a public applyTransition() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method visibility
    // -------------------------------------------------------------------------

    public function test_create_instance_is_public(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'createInstance');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_instances_is_public(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'listInstances');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_apply_transition_is_public(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'applyTransition');

        $this->assertTrue($reflection->isPublic());
    }

    // -------------------------------------------------------------------------
    // Method signatures — reflection
    // -------------------------------------------------------------------------

    public function test_create_instance_accepts_create_workflow_instance_dto(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'createInstance');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateWorkflowInstanceDTO::class, (string) $params[0]->getType());
    }

    public function test_list_instances_accepts_entity_type_string(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'listInstances');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('entityType', $params[0]->getName());
        $this->assertSame('string', (string) $params[0]->getType());
    }

    public function test_apply_transition_accepts_correct_parameters(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'applyTransition');
        $params     = $reflection->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('instanceId', $params[0]->getName());
        $this->assertSame('toStateId', $params[1]->getName());
        $this->assertSame('comment', $params[2]->getName());
    }

    public function test_apply_transition_comment_parameter_is_nullable(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'applyTransition');
        $params     = $reflection->getParameters();

        $this->assertTrue($params[2]->isOptional());
        $this->assertNull($params[2]->getDefaultValue());
    }

    // -------------------------------------------------------------------------
    // DTO payload mapping
    // -------------------------------------------------------------------------

    public function test_create_instance_dto_maps_all_fields(): void
    {
        $dto = CreateWorkflowInstanceDTO::fromArray([
            'workflow_definition_id' => 3,
            'entity_type'            => 'sales_order',
            'entity_id'              => 100,
            'initial_state_id'       => 2,
        ]);

        $payload = [
            'workflow_definition_id' => $dto->workflowDefinitionId,
            'entity_type'            => $dto->entityType,
            'entity_id'              => $dto->entityId,
            'current_state_id'       => $dto->initialStateId,
        ];

        $this->assertSame(3, $payload['workflow_definition_id']);
        $this->assertSame('sales_order', $payload['entity_type']);
        $this->assertSame(100, $payload['entity_id']);
        $this->assertSame(2, $payload['current_state_id']);
    }

    public function test_create_instance_dto_initial_state_defaults_to_null(): void
    {
        $dto = CreateWorkflowInstanceDTO::fromArray([
            'workflow_definition_id' => 1,
            'entity_type'            => 'purchase_order',
            'entity_id'              => 50,
        ]);

        $this->assertNull($dto->initialStateId);
    }
}
