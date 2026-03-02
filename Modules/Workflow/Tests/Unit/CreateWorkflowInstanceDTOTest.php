<?php

declare(strict_types=1);

namespace Modules\Workflow\Tests\Unit;

use Modules\Workflow\Application\DTOs\CreateWorkflowInstanceDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateWorkflowInstanceDTO.
 */
class CreateWorkflowInstanceDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = CreateWorkflowInstanceDTO::fromArray([
            'workflow_definition_id' => 1,
            'entity_type'            => 'sales_order',
            'entity_id'              => 42,
        ]);

        $this->assertSame(1, $dto->workflowDefinitionId);
        $this->assertSame('sales_order', $dto->entityType);
        $this->assertSame(42, $dto->entityId);
        $this->assertNull($dto->initialStateId);
    }

    public function test_from_array_accepts_initial_state_id(): void
    {
        $dto = CreateWorkflowInstanceDTO::fromArray([
            'workflow_definition_id' => 2,
            'entity_type'            => 'purchase_order',
            'entity_id'              => 10,
            'initial_state_id'       => 5,
        ]);

        $this->assertSame(5, $dto->initialStateId);
    }

    public function test_initial_state_id_defaults_to_null(): void
    {
        $dto = CreateWorkflowInstanceDTO::fromArray([
            'workflow_definition_id' => 3,
            'entity_type'            => 'crm_lead',
            'entity_id'              => 7,
        ]);

        $this->assertNull($dto->initialStateId);
    }

    public function test_ids_cast_to_int(): void
    {
        $dto = CreateWorkflowInstanceDTO::fromArray([
            'workflow_definition_id' => '4',
            'entity_type'            => 'crm_opportunity',
            'entity_id'              => '15',
            'initial_state_id'       => '3',
        ]);

        $this->assertIsInt($dto->workflowDefinitionId);
        $this->assertIsInt($dto->entityId);
        $this->assertIsInt($dto->initialStateId);
        $this->assertSame(4, $dto->workflowDefinitionId);
        $this->assertSame(15, $dto->entityId);
        $this->assertSame(3, $dto->initialStateId);
    }

    public function test_to_array_returns_correct_keys(): void
    {
        $dto   = CreateWorkflowInstanceDTO::fromArray([
            'workflow_definition_id' => 1,
            'entity_type'            => 'sales_order',
            'entity_id'              => 1,
        ]);
        $array = $dto->toArray();

        $this->assertArrayHasKey('workflow_definition_id', $array);
        $this->assertArrayHasKey('entity_type', $array);
        $this->assertArrayHasKey('entity_id', $array);
        $this->assertArrayHasKey('initial_state_id', $array);
    }

    public function test_to_array_round_trips_correctly(): void
    {
        $data = [
            'workflow_definition_id' => 2,
            'entity_type'            => 'invoice',
            'entity_id'              => 99,
            'initial_state_id'       => 4,
        ];

        $dto   = CreateWorkflowInstanceDTO::fromArray($data);
        $array = $dto->toArray();

        $this->assertSame($data['workflow_definition_id'], $array['workflow_definition_id']);
        $this->assertSame($data['entity_type'], $array['entity_type']);
        $this->assertSame($data['entity_id'], $array['entity_id']);
        $this->assertSame($data['initial_state_id'], $array['initial_state_id']);
    }
}
