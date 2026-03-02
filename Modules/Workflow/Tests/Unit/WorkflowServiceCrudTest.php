<?php

declare(strict_types=1);

namespace Modules\Workflow\Tests\Unit;

use Modules\Workflow\Application\Services\WorkflowService;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryContract;
use Modules\Workflow\Domain\Entities\WorkflowInstance;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural compliance tests for WorkflowService CRUD extension methods.
 *
 * Verifies showDefinition(), showInstance(), and deleteDefinition() exist,
 * are public, and carry correct signatures. Pure-PHP — no Laravel bootstrap.
 */
class WorkflowServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence
    // -------------------------------------------------------------------------

    public function test_workflow_service_has_show_definition_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'showDefinition'),
            'WorkflowService must expose showDefinition().'
        );
    }

    public function test_workflow_service_has_show_instance_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'showInstance'),
            'WorkflowService must expose showInstance().'
        );
    }

    public function test_workflow_service_has_delete_definition_method(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'deleteDefinition'),
            'WorkflowService must expose deleteDefinition().'
        );
    }

    // -------------------------------------------------------------------------
    // Visibility
    // -------------------------------------------------------------------------

    public function test_show_definition_is_public(): void
    {
        $ref = new ReflectionMethod(WorkflowService::class, 'showDefinition');
        $this->assertTrue($ref->isPublic());
    }

    public function test_show_instance_is_public(): void
    {
        $ref = new ReflectionMethod(WorkflowService::class, 'showInstance');
        $this->assertTrue($ref->isPublic());
    }

    public function test_delete_definition_is_public(): void
    {
        $ref = new ReflectionMethod(WorkflowService::class, 'deleteDefinition');
        $this->assertTrue($ref->isPublic());
    }

    // -------------------------------------------------------------------------
    // Signatures
    // -------------------------------------------------------------------------

    public function test_show_definition_accepts_id(): void
    {
        $ref    = new ReflectionMethod(WorkflowService::class, 'showDefinition');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_instance_accepts_id(): void
    {
        $ref    = new ReflectionMethod(WorkflowService::class, 'showInstance');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_delete_definition_accepts_id(): void
    {
        $ref    = new ReflectionMethod(WorkflowService::class, 'deleteDefinition');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // Return types
    // -------------------------------------------------------------------------

    public function test_delete_definition_return_type_is_bool(): void
    {
        $ref        = new ReflectionMethod(WorkflowService::class, 'deleteDefinition');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame('bool', $returnType);
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_workflow_service_instantiates_with_repository_contract(): void
    {
        $repo    = $this->createMock(WorkflowRepositoryContract::class);
        $service = new WorkflowService($repo);

        $this->assertInstanceOf(WorkflowService::class, $service);
    }
}
