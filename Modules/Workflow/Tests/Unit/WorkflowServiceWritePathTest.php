<?php

declare(strict_types=1);

namespace Modules\Workflow\Tests\Unit;

use Modules\Workflow\Application\DTOs\CreateWorkflowDTO;
use Modules\Workflow\Application\Services\WorkflowService;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural compliance tests for WorkflowService write-path methods.
 *
 * create(), update(), and delete() call DB::transaction() internally,
 * which requires a full Laravel bootstrap, so functional tests live in
 * feature tests.  These pure-PHP tests verify method signatures and
 * DTO field-mapping contracts.
 */
class WorkflowServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_workflow_service_has_create_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'create'),
            'WorkflowService must expose a public create() method.'
        );
    }

    public function test_workflow_service_has_update_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'update'),
            'WorkflowService must expose a public update() method.'
        );
    }

    public function test_workflow_service_has_delete_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'delete'),
            'WorkflowService must expose a public delete() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_accepts_create_workflow_dto(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'create');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateWorkflowDTO::class, (string) $params[0]->getType());
    }

    public function test_update_accepts_id_and_data_array(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'update');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_delete_accepts_single_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'delete');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // DTO field mapping — mirrors create() create payload
    // -------------------------------------------------------------------------

    public function test_create_payload_maps_dto_fields_correctly(): void
    {
        $dto = CreateWorkflowDTO::fromArray([
            'name'        => 'Invoice Approval',
            'entity_type' => 'invoice',
            'description' => 'Multi-step invoice approval workflow',
            'is_active'   => true,
        ]);

        $createPayload = [
            'name'        => $dto->name,
            'entity_type' => $dto->entityType,
            'description' => $dto->description,
            'is_active'   => $dto->isActive,
        ];

        $this->assertSame('Invoice Approval', $createPayload['name']);
        $this->assertSame('invoice', $createPayload['entity_type']);
        $this->assertSame('Multi-step invoice approval workflow', $createPayload['description']);
        $this->assertTrue($createPayload['is_active']);
    }

    public function test_create_payload_null_description_preserved(): void
    {
        $dto = CreateWorkflowDTO::fromArray([
            'name'        => 'Simple Workflow',
            'entity_type' => 'sales_order',
        ]);

        $createPayload = [
            'name'        => $dto->name,
            'entity_type' => $dto->entityType,
            'description' => $dto->description,
            'is_active'   => $dto->isActive,
        ];

        $this->assertNull($createPayload['description']);
        $this->assertTrue($createPayload['is_active']);
    }

    public function test_create_payload_is_active_false_preserved(): void
    {
        $dto = CreateWorkflowDTO::fromArray([
            'name'        => 'Inactive Workflow',
            'entity_type' => 'crm_lead',
            'is_active'   => false,
        ]);

        $createPayload = [
            'name'        => $dto->name,
            'entity_type' => $dto->entityType,
            'description' => $dto->description,
            'is_active'   => $dto->isActive,
        ];

        $this->assertFalse($createPayload['is_active']);
    }
}
